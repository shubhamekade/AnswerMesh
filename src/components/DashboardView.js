/**
 * Dashboard Overview Component.
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import ScoreGauge from './ScoreGauge';

export default function DashboardView({ onNavigateTab }) {
    const [dashboardData, setDashboardData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [crawling, setCrawling] = useState(false);
    const [crawlProgress, setCrawlProgress] = useState(null);
    const [notice, setNotice] = useState(null);

    const loadDashboard = () => {
        setLoading(true);
        apiFetch({ path: '/aiseo/v1/dashboard' })
            .then((res) => {
                setDashboardData(res.data);
                setLoading(false);
            })
            .catch((err) => {
                setNotice({ type: 'error', message: err.message || __('Failed to load dashboard metrics.', 'answermesh') });
                setLoading(false);
            });
    };

    useEffect(() => {
        loadDashboard();
    }, []);

    const handleStartCrawl = async () => {
        setCrawling(true);
        setNotice(null);
        setCrawlProgress(__('Initiating audit...', 'answermesh'));

        try {
            const startRes = await apiFetch({ path: '/aiseo/v1/crawl/start', method: 'POST' });
            let currentStatus = startRes.data;

            // Direct interactive batch loop: processes all pages without waiting for background cron
            let maxBatches = 60; // Safety guard
            while (currentStatus && currentStatus.status === 'running' && maxBatches-- > 0) {
                const total = currentStatus.total || 0;
                const processed = currentStatus.processed || 0;
                setCrawlProgress(sprintf(__('Auditing pages (%d / %d)...', 'answermesh'), processed, total));

                const batchRes = await apiFetch({
                    path: '/aiseo/v1/crawl/process-batch',
                    method: 'POST',
                    data: { batch_size: 5 },
                });
                currentStatus = batchRes.data;

                if (!currentStatus || currentStatus.status !== 'running') {
                    break;
                }
            }

            setCrawling(false);
            setCrawlProgress(null);
            setNotice({
                type: 'success',
                message: __('Site analysis completed! Diagnostic scores and recommendations are now live.', 'answermesh'),
            });
            loadDashboard();
        } catch (err) {
            setCrawling(false);
            setCrawlProgress(null);
            setNotice({
                type: 'error',
                message: err.message || __('Failed to complete site audit.', 'answermesh'),
            });
            loadDashboard();
        }
    };

    if (loading) {
        return (
            <div className="answermesh-loading-panel">
                <Spinner />
                <p>{__('Calculating site diagnostic scores...', 'answermesh')}</p>
            </div>
        );
    }

    const { scores, rating, priorities, crawl_status } = dashboardData || {};
    const analyzedCount = Number(dashboardData?.analyzed_posts ?? dashboardData?.analyzed_pages_count ?? 0);
    const totalCount = Number(dashboardData?.total_posts ?? dashboardData?.total_posts_count ?? 0);

    return (
        <div className="answermesh-dashboard-view">
            {notice && (
                <Notice
                    status={notice.type}
                    isDismissible
                    onDismiss={() => setNotice(null)}
                    className="answermesh-notice-banner"
                >
                    {notice.message}
                </Notice>
            )}

            <div className="answermesh-dashboard-top-grid">
                <ScoreGauge
                    scores={scores}
                    rating={rating}
                    summary={__('Site-wide audit score based on analyzed posts and schema structure.', 'answermesh')}
                />

                <div className="answermesh-stats-card">
                    <h3 className="answermesh-card-title">{__('Audit & Crawl Coverage', 'answermesh')}</h3>
                    <div className="answermesh-stat-tiles">
                        <div className="answermesh-stat-tile">
                            <span className="answermesh-stat-number">{analyzedCount}</span>
                            <span className="answermesh-stat-label">{__('Pages Analyzed', 'answermesh')}</span>
                        </div>
                        <div className="answermesh-stat-tile">
                            <span className="answermesh-stat-number">{totalCount}</span>
                            <span className="answermesh-stat-label">{__('Total Indexed', 'answermesh')}</span>
                        </div>
                    </div>

                    <div className="answermesh-crawl-control">
                        <p className="answermesh-crawl-desc">
                            {__('Run site crawler to inspect heading hierarchy, question clarity, and schema output.', 'answermesh')}
                        </p>
                        <Button
                            variant="primary"
                            onClick={handleStartCrawl}
                            isBusy={crawling}
                            disabled={crawling}
                            className="answermesh-primary-action-btn"
                        >
                            {crawling ? (crawlProgress || __('Auditing...', 'answermesh')) : __('Run Site Analysis', 'answermesh')}
                        </Button>
                    </div>
                </div>
            </div>

            <div className="answermesh-priority-section">
                <div className="answermesh-section-header">
                    <h3>{__('Top Priority Recommendations', 'answermesh')}</h3>
                    {onNavigateTab && (
                        <Button variant="link" onClick={() => onNavigateTab('recommendations')}>
                            {__('View All Recommendations →', 'answermesh')}
                        </Button>
                    )}
                </div>

                {(!priorities || priorities.length === 0) ? (
                    <div className="answermesh-empty-card">
                        <p>{__('No critical issues found! Run a site audit to scan posts.', 'answermesh')}</p>
                    </div>
                ) : (
                    <div className="answermesh-priority-list">
                        {priorities.map((item, idx) => (
                            <div key={item.id || idx} className={`answermesh-priority-item priority-${(item.priority || 'medium').toLowerCase()}`}>
                                <div className="answermesh-priority-badge">{item.priority}</div>
                                <div className="answermesh-priority-content">
                                    <h4 className="answermesh-item-observation">{item.observation}</h4>
                                    <p className="answermesh-item-rec">{item.recommendation}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
