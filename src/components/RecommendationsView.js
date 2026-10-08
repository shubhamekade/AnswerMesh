/**
 * Recommendations Feed Component.
 * Displays prioritized audit items adhering strictly to the 7-attribute Evidence Model.
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, Notice, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function RecommendationsView() {
    const [recommendations, setRecommendations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filterPriority, setFilterPriority] = useState('ALL');
    const [notice, setNotice] = useState(null);
    const [expandedIds, setExpandedIds] = useState(new Set());

    const loadRecommendations = () => {
        setLoading(true);
        apiFetch({ path: '/aiseo/v1/recommendations' })
            .then((res) => {
                setRecommendations(res.data || []);
                setLoading(false);
            })
            .catch((err) => {
                setNotice({ type: 'error', message: err.message || __('Failed to load recommendations.', 'answermesh') });
                setLoading(false);
            });
    };

    useEffect(() => {
        loadRecommendations();
    }, []);

    const toggleExpand = (id) => {
        setExpandedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const filtered = recommendations.filter((item) => {
        if (filterPriority === 'ALL') return true;
        return (item.priority || '').toUpperCase() === filterPriority;
    });

    const getPriorityBadgeClass = (priority) => {
        const p = (priority || 'MEDIUM').toUpperCase();
        switch (p) {
            case 'CRITICAL': return 'priority-badge-critical';
            case 'HIGH': return 'priority-badge-high';
            case 'MEDIUM': return 'priority-badge-medium';
            default: return 'priority-badge-low';
        }
    };

    if (loading) {
        return (
            <div className="answermesh-loading-panel">
                <Spinner />
                <p>{__('Loading recommendations with Evidence Model audit data...', 'answermesh')}</p>
            </div>
        );
    }

    return (
        <div className="answermesh-recommendations-view">
            <div className="answermesh-feed-header">
                <div>
                    <h2>{__('Actionable Recommendations Feed', 'answermesh')}</h2>
                    <p className="answermesh-feed-subtitle">
                        {__('Prioritized fixes backed by verifiable evidence, impact analysis, and acceptance criteria.', 'answermesh')}
                    </p>
                </div>
                <div className="answermesh-filter-box">
                    <SelectControl
                        label={__('Filter Priority', 'answermesh')}
                        value={filterPriority}
                        options={[
                            { label: __('All Priorities', 'answermesh'), value: 'ALL' },
                            { label: __('Critical', 'answermesh'), value: 'CRITICAL' },
                            { label: __('High', 'answermesh'), value: 'HIGH' },
                            { label: __('Medium', 'answermesh'), value: 'MEDIUM' },
                            { label: __('Low', 'answermesh'), value: 'LOW' },
                        ]}
                        onChange={setFilterPriority}
                    />
                </div>
            </div>

            {notice && (
                <Notice status={notice.type} isDismissible onDismiss={() => setNotice(null)}>
                    {notice.message}
                </Notice>
            )}

            {filtered.length === 0 ? (
                <div className="answermesh-empty-card">
                    <p>{__('No recommendations match current filter criteria.', 'answermesh')}</p>
                </div>
            ) : (
                <div className="answermesh-evidence-list">
                    {filtered.map((item) => {
                        const isExpanded = expandedIds.has(item.id);
                        return (
                            <div key={item.id} className="answermesh-evidence-card">
                                <div className="answermesh-evidence-header" onClick={() => toggleExpand(item.id)}>
                                    <div className="answermesh-evidence-title-group">
                                        <span className={`answermesh-badge ${getPriorityBadgeClass(item.priority)}`}>
                                            {item.priority || 'MEDIUM'}
                                        </span>
                                        <h3 className="answermesh-evidence-title">{item.observation}</h3>
                                    </div>
                                    <div className="answermesh-evidence-header-actions">
                                        <span className="answermesh-confidence-tag">
                                            {__('Confidence: ', 'answermesh')}
                                            <strong>{item.confidence || 'Medium'}</strong>
                                        </span>
                                        <Button variant="tertiary" className="answermesh-expand-toggle">
                                            {isExpanded ? __('▲ Details', 'answermesh') : __('▼ Details', 'answermesh')}
                                        </Button>
                                    </div>
                                </div>

                                <div className="answermesh-evidence-summary">
                                    <p><strong>{__('Action: ', 'answermesh')}</strong>{item.recommendation}</p>
                                </div>

                                {isExpanded && (
                                    <div className="answermesh-evidence-body">
                                        <div className="answermesh-evidence-attr">
                                            <span className="attr-label">{__('Verifiable Evidence:', 'answermesh')}</span>
                                            <code className="attr-code">{item.evidence || __('N/A', 'answermesh')}</code>
                                        </div>

                                        <div className="answermesh-evidence-attr">
                                            <span className="attr-label">{__('AI & Search Impact:', 'answermesh')}</span>
                                            <p className="attr-text">{item.impact || __('N/A', 'answermesh')}</p>
                                        </div>

                                        <div className="answermesh-evidence-attr">
                                            <span className="attr-label">{__('Acceptance Check:', 'answermesh')}</span>
                                            <div className="attr-check-box">
                                                <span className="check-icon">✓</span>
                                                <span>{item.acceptance_check || __('Re-run page audit to verify.', 'answermesh')}</span>
                                            </div>
                                        </div>

                                        {item.post_id && (
                                            <div className="answermesh-evidence-actions">
                                                <a
                                                    href={`post.php?post=${item.post_id}&action=edit`}
                                                    className="button button-primary"
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    {__('Open in Editor →', 'answermesh')}
                                                </a>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
