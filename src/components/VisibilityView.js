/**
 * AI Search Visibility Monitoring Dashboard.
 *
 * @package AnswerMesh
 */

import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    Card,
    CardHeader,
    CardBody,
    Spinner,
    Notice,
    SelectControl,
    TextControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function VisibilityView() {
    const [summary, setSummary] = useState(null);
    const [index, setIndex] = useState(null);
    const [observations, setObservations] = useState([]);
    const [competitors, setCompetitors] = useState([]);
    const [loading, setLoading] = useState(true);
    const [notice, setNotice] = useState(null);

    // Filters & On-demand test
    const [platformFilter, setPlatformFilter] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [testQuery, setTestQuery] = useState('');
    const [testingQuery, setTestingQuery] = useState(false);
    const [testResult, setTestResult] = useState(null);
    const [runningSample, setRunningSample] = useState(false);

    const loadData = async () => {
        setLoading(true);
        try {
            const [sumRes, obsRes, compRes] = await Promise.all([
                apiFetch({ path: '/aiseo/v1/visibility/summary?days=30' }),
                apiFetch({ path: `/aiseo/v1/visibility/observations?limit=50&platform=${platformFilter}` }),
                apiFetch({ path: '/aiseo/v1/visibility/competitors?limit=10' }),
            ]);

            setSummary(sumRes.summary || {});
            setIndex(sumRes.index || {});
            setObservations(obsRes.observations || []);
            setCompetitors(compRes.competitors || []);
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to load AI visibility data.', 'answermesh'),
            });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, [platformFilter]);

    const handleRunBatch = async () => {
        setRunningSample(true);
        try {
            const res = await apiFetch({
                path: '/aiseo/v1/visibility/run-sample',
                method: 'POST',
                data: { batch_size: 5 },
            });
            setNotice({
                status: 'success',
                message: sprintf(__('Sample batch completed. %d queries analyzed.', 'answermesh'), res.result?.sampled_count || 0),
            });
            await loadData();
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to run visibility sampling batch.', 'answermesh'),
            });
        } finally {
            setRunningSample(false);
        }
    };

    const handleTestSingleQuery = async () => {
        if (!testQuery.trim()) return;
        setTestingQuery(true);
        setTestResult(null);
        try {
            const res = await apiFetch({
                path: '/aiseo/v1/visibility/run-sample',
                method: 'POST',
                data: { query: testQuery.trim(), platform: 'all' },
            });
            setTestResult(res.result?.platforms || null);
            setNotice({
                status: 'success',
                message: sprintf(__('Observation recorded across all platforms for "%s".', 'answermesh'), testQuery),
            });
            await loadData();
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to test query.', 'answermesh'),
            });
        } finally {
            setTestingQuery(false);
        }
    };

    const handlePrune = async () => {
        if (!window.confirm(__('Prune observation records older than 90 days?', 'answermesh'))) {
            return;
        }
        try {
            const res = await apiFetch({
                path: '/aiseo/v1/visibility/prune?days=90',
                method: 'DELETE',
            });
            setNotice({
                status: 'info',
                message: res.message || __('Old records pruned.', 'answermesh'),
            });
            await loadData();
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to prune old records.', 'answermesh'),
            });
        }
    };

    const handleExportCSV = () => {
        if (!observations.length) return;
        const headers = ['ID', 'Date', 'Query', 'Platform', 'Observed', 'Mention', 'Source URL', 'Competitors'];
        const rows = observations.map(obs => [
            obs.id,
            `"${obs.captured_at}"`,
            `"${(obs.query || '').replace(/"/g, '""')}"`,
            `"${obs.platform}"`,
            obs.observed ? 'Yes' : 'No',
            obs.mention ? 'Yes' : 'No',
            `"${obs.source_url || ''}"`,
            `"${(obs.competitors || []).map(c => c.domain).join('; ')}"`,
        ]);

        const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `answermesh-ai-visibility-${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    const filteredObservations = observations.filter(obs => {
        if (!searchQuery) return true;
        return (obs.query || '').toLowerCase().includes(searchQuery.toLowerCase());
    });

    if (loading && !summary) {
        return (
            <div className="aiseo-loading-container">
                <Spinner />
                <p>{__('Loading AI Search Visibility Engine...', 'answermesh')}</p>
            </div>
        );
    }

    const score = index?.score ?? 0;
    const grade = index?.grade ?? 'Nascent';
    const platforms = summary?.platforms ?? {};

    return (
        <div className="aiseo-visibility-view">
            <div className="aiseo-view-header">
                <div>
                    <h2>{__('Observed AI Search Visibility', 'answermesh')}</h2>
                    <p className="aiseo-view-desc">
                        {__('Empirical observation logs and citation tracking across ChatGPT Search, Google Gemini, Perplexity AI, and Microsoft Copilot.', 'answermesh')}
                    </p>
                </div>
                <div className="aiseo-header-actions">
                    <Button 
                        isSecondary 
                        onClick={handleExportCSV}
                        disabled={!observations.length}
                    >
                        {__('Export CSV', 'answermesh')}
                    </Button>
                    <Button 
                        isSecondary 
                        isDestructive 
                        onClick={handlePrune}
                    >
                        {__('Prune >90d', 'answermesh')}
                    </Button>
                    <Button 
                        isPrimary 
                        onClick={handleRunBatch}
                        isBusy={runningSample}
                        disabled={runningSample}
                    >
                        {__('Run Sample Batch', 'answermesh')}
                    </Button>
                </div>
            </div>

            {notice && (
                <Notice
                    status={notice.status}
                    isDismissible
                    onDismiss={() => setNotice(null)}
                    className="aiseo-notice"
                >
                    {notice.message}
                </Notice>
            )}

            {/* Metrics Overview Grid */}
            <div className="aiseo-visibility-stats-grid">
                <Card className="aiseo-stat-card aiseo-stat-index">
                    <CardBody>
                        <div className="aiseo-stat-label">{__('AI Visibility Index', 'answermesh')}</div>
                        <div className="aiseo-index-value-row">
                            <span className="aiseo-stat-value">{score}</span>
                            <span className={`aiseo-grade-badge aiseo-grade-${grade.toLowerCase()}`}>
                                {grade}
                            </span>
                        </div>
                        <div className="aiseo-stat-sub">
                            {sprintf(__('Rolling 30-day composite score', 'answermesh'))}
                        </div>
                    </CardBody>
                </Card>

                <Card className="aiseo-stat-card">
                    <CardBody>
                        <div className="aiseo-stat-label">{__('Citation Rate', 'answermesh')}</div>
                        <div className="aiseo-stat-value">{summary?.citation_rate ?? 0}%</div>
                        <div className="aiseo-stat-sub">
                            {sprintf(__('%d direct URL citations', 'answermesh'), summary?.total_citations ?? 0)}
                        </div>
                    </CardBody>
                </Card>

                <Card className="aiseo-stat-card">
                    <CardBody>
                        <div className="aiseo-stat-label">{__('Brand Mention Rate', 'answermesh')}</div>
                        <div className="aiseo-stat-value">{summary?.mention_rate ?? 0}%</div>
                        <div className="aiseo-stat-sub">
                            {sprintf(__('%d brand name occurrences', 'answermesh'), summary?.total_mentions ?? 0)}
                        </div>
                    </CardBody>
                </Card>

                <Card className="aiseo-stat-card">
                    <CardBody>
                        <div className="aiseo-stat-label">{__('Total Queries Monitored', 'answermesh')}</div>
                        <div className="aiseo-stat-value">{summary?.total_checks ?? 0}</div>
                        <div className="aiseo-stat-sub">
                            {sprintf(__('%d observed present (%s%%)', 'answermesh'), summary?.total_observed ?? 0, summary?.observed_rate ?? 0)}
                        </div>
                    </CardBody>
                </Card>
            </div>

            {/* Platform Comparison Cards */}
            <h3 className="aiseo-section-title">{__('Platform Coverage Breakdown', 'answermesh')}</h3>
            <div className="aiseo-platform-grid">
                {Object.entries(platforms).map(([key, plat]) => (
                    <Card key={key} className="aiseo-platform-card">
                        <CardHeader>
                            <strong>{plat.name}</strong>
                            <span className="aiseo-plat-rate">{plat.rate}%</span>
                        </CardHeader>
                        <CardBody>
                            <div className="aiseo-plat-metric-row">
                                <span>{__('Queries Checked:', 'answermesh')}</span>
                                <strong>{plat.checks}</strong>
                            </div>
                            <div className="aiseo-plat-metric-row">
                                <span>{__('Site Observed:', 'answermesh')}</span>
                                <strong>{plat.observed}</strong>
                            </div>
                            <div className="aiseo-plat-metric-row">
                                <span>{__('Citations Captured:', 'answermesh')}</span>
                                <strong>{plat.citations}</strong>
                            </div>
                            <div className="aiseo-progress-track">
                                <div 
                                    className="aiseo-progress-fill aiseo-progress-accent" 
                                    style={{ width: `${Math.min(100, plat.rate)}%` }} 
                                />
                            </div>
                        </CardBody>
                    </Card>
                ))}
            </div>

            {/* Interactive Single Query Tester */}
            <Card className="aiseo-query-tester-card">
                <CardHeader>
                    <strong>{__('Live AI Search Sampler', 'answermesh')}</strong>
                </CardHeader>
                <CardBody>
                    <p className="aiseo-card-desc">
                        {__('Test a target commercial question or keyword to observe citation presence across all engines.', 'answermesh')}
                    </p>
                    <div className="aiseo-query-input-row">
                        <TextControl
                            placeholder={__('e.g. Best Cambridge School in Pune, or What is AnswerMesh?', 'answermesh')}
                            value={testQuery}
                            onChange={setTestQuery}
                        />
                        <Button 
                            isPrimary 
                            onClick={handleTestSingleQuery}
                            isBusy={testingQuery}
                            disabled={testingQuery || !testQuery.trim()}
                        >
                            {__('Test Query', 'answermesh')}
                        </Button>
                    </div>

                    {testResult && (
                        <div className="aiseo-test-result-box">
                            <h4>{sprintf(__('Results for: "%s"', 'answermesh'), testQuery)}</h4>
                            <div className="aiseo-test-result-grid">
                                {Object.entries(testResult).map(([platKey, res]) => (
                                    <div key={platKey} className="aiseo-test-item">
                                        <div className="aiseo-test-plat-head">
                                            <strong>{platforms[platKey]?.name || platKey}</strong>
                                            <span className={`aiseo-badge ${res.observed ? 'aiseo-badge-fresh' : 'aiseo-badge-critical'}`}>
                                                {res.observed ? __('Observed', 'answermesh') : __('Absent', 'answermesh')}
                                            </span>
                                        </div>
                                        <p className="aiseo-test-snippet">{res.position_context}</p>
                                        {res.source_url && (
                                            <div className="aiseo-test-citation">
                                                <span>{__('Citation: ', 'answermesh')}</span>
                                                <a href={res.source_url} target="_blank" rel="noopener noreferrer">
                                                    {res.source_url}
                                                </a>
                                            </div>
                                        )}
                                        {res.competitors?.length > 0 && (
                                            <div className="aiseo-test-comps">
                                                <span>{__('Rivals Detected: ', 'answermesh')}</span>
                                                {res.competitors.map(c => c.domain).join(', ')}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </CardBody>
            </Card>

            {/* Competitors & Share of Voice */}
            {competitors.length > 0 && (
                <Card className="aiseo-competitors-card">
                    <CardHeader>
                        <strong>{__('Observed Competitor Share of Voice', 'answermesh')}</strong>
                    </CardHeader>
                    <CardBody>
                        <div className="aiseo-competitor-list">
                            {competitors.map(comp => (
                                <div key={comp.domain} className="aiseo-comp-row">
                                    <div className="aiseo-comp-info">
                                        <strong>{comp.name}</strong>
                                        <span className="aiseo-comp-domain">({comp.domain})</span>
                                    </div>
                                    <div className="aiseo-comp-bar-container">
                                        <div 
                                            className="aiseo-comp-bar-fill" 
                                            style={{ width: `${Math.min(100, comp.share_percent * 2)}%` }} 
                                        />
                                    </div>
                                    <div className="aiseo-comp-stats">
                                        <span>{comp.count} citations</span>
                                        <strong>{comp.share_percent}%</strong>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardBody>
                </Card>
            )}

            {/* Query Observation History Table */}
            <Card className="aiseo-observations-table-card">
                <CardHeader>
                    <strong>{__('Recent Observation Logs', 'answermesh')}</strong>
                    <div className="aiseo-table-controls">
                        <SelectControl
                            value={platformFilter}
                            options={[
                                { label: __('All Platforms', 'answermesh'), value: 'all' },
                                { label: 'ChatGPT Search', value: 'chatgpt' },
                                { label: 'Google Gemini', value: 'gemini' },
                                { label: 'Perplexity AI', value: 'perplexity' },
                                { label: 'Microsoft Copilot', value: 'copilot' },
                            ]}
                            onChange={setPlatformFilter}
                        />
                        <TextControl
                            placeholder={__('Search query...', 'answermesh')}
                            value={searchQuery}
                            onChange={setSearchQuery}
                        />
                    </div>
                </CardHeader>
                <CardBody>
                    {filteredObservations.length === 0 ? (
                        <p className="aiseo-empty-text">
                            {__('No query observation logs found. Click "Run Sample Batch" to begin sampling.', 'answermesh')}
                        </p>
                    ) : (
                        <table className="aiseo-data-table">
                            <thead>
                                <tr>
                                    <th>{__('Date', 'answermesh')}</th>
                                    <th>{__('Query', 'answermesh')}</th>
                                    <th>{__('Platform', 'answermesh')}</th>
                                    <th>{__('Status', 'answermesh')}</th>
                                    <th>{__('Source Citation / Context', 'answermesh')}</th>
                                    <th>{__('Competitors', 'answermesh')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredObservations.map(obs => (
                                    <tr key={obs.id}>
                                        <td className="aiseo-date-col">{obs.captured_at?.slice(0, 10)}</td>
                                        <td><strong>{obs.query}</strong></td>
                                        <td>
                                            <span className="aiseo-plat-tag">
                                                {platforms[obs.platform]?.name || obs.platform}
                                            </span>
                                        </td>
                                        <td>
                                            {obs.observed ? (
                                                <span className="aiseo-badge aiseo-badge-fresh">
                                                    {obs.source_url ? __('Cited', 'answermesh') : __('Mentioned', 'answermesh')}
                                                </span>
                                            ) : (
                                                <span className="aiseo-badge aiseo-badge-critical">
                                                    {__('Absent', 'answermesh')}
                                                </span>
                                            )}
                                        </td>
                                        <td className="aiseo-context-col">
                                            {obs.source_url ? (
                                                <a href={obs.source_url} target="_blank" rel="noopener noreferrer" className="aiseo-citation-link">
                                                    {obs.source_url}
                                                </a>
                                            ) : (
                                                <span className="aiseo-snippet-text">{obs.position_context || '—'}</span>
                                            )}
                                        </td>
                                        <td>
                                            {obs.competitors?.length > 0 ? (
                                                <span className="aiseo-comps-summary">
                                                    {obs.competitors.map(c => c.name || c.domain).join(', ')}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}
