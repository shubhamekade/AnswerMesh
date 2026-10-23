/**
 * Links & Freshness Management Hub.
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
    TabPanel,
    SelectControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function LinkDecayView() {
    const [decaySummary, setDecaySummary] = useState(null);
    const [decayInventory, setDecayInventory] = useState([]);
    const [linkGraph, setLinkGraph] = useState(null);
    const [linkOpportunities, setLinkOpportunities] = useState([]);
    const [loading, setLoading] = useState(true);
    const [notice, setNotice] = useState(null);
    const [tierFilter, setTierFilter] = useState('ALL');

    const loadData = async () => {
        setLoading(true);
        try {
            const [sumRes, invRes, graphRes, oppRes] = await Promise.all([
                apiFetch({ path: '/aiseo/v1/decay/summary' }),
                apiFetch({ path: '/aiseo/v1/decay/inventory' }),
                apiFetch({ path: '/aiseo/v1/links/graph' }),
                apiFetch({ path: '/aiseo/v1/links/opportunities' }),
            ]);

            setDecaySummary(sumRes.summary || {});
            setDecayInventory(invRes.inventory || []);
            setLinkGraph(graphRes.graph || {});
            setLinkOpportunities(oppRes.opportunities || []);
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to load link & decay data.', 'answermesh'),
            });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, []);

    const filteredInventory = decayInventory.filter((item) => {
        if (tierFilter === 'ALL') return true;
        return item.tier?.toUpperCase() === tierFilter;
    });

    if (loading) {
        return (
            <div className="answermesh-loading-center">
                <Spinner />
                <p>{__('Loading links & content freshness data...', 'answermesh')}</p>
            </div>
        );
    }

    const totalLinks = linkGraph?.total_links || 0;
    const orphanCount = linkGraph?.orphan_count || 0;
    const avgFreshness = decaySummary?.avg_freshness || 100;
    const needsRefresh = decaySummary?.needs_refresh || 0;

    return (
        <div className="answermesh-link-decay-view">
            {notice && (
                <Notice status={notice.status} onDismiss={() => setNotice(null)} className="answermesh-notice">
                    {notice.message}
                </Notice>
            )}

            {/* Metrics Strip */}
            <div className="answermesh-metric-strip">
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('Total Internal Links', 'answermesh')}</span>
                    <span className="metric-value">{totalLinks}</span>
                </div>
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('True Orphan Pages', 'answermesh')}</span>
                    <span className={`metric-value ${orphanCount > 0 ? 'alert-orange' : ''}`}>{orphanCount}</span>
                </div>
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('Average Freshness', 'answermesh')}</span>
                    <span className="metric-value">{avgFreshness}%</span>
                </div>
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('Need Content Refresh', 'answermesh')}</span>
                    <span className={`metric-value ${needsRefresh > 0 ? 'alert-red' : ''}`}>{needsRefresh}</span>
                </div>
            </div>

            {/* Main Tabs: Content Freshness Queue vs Link Equity */}
            <TabPanel
                className="answermesh-subtabs"
                activeClass="is-active"
                tabs={[
                    {
                        name: 'freshness',
                        title: __('Content Decay & Freshness Queue', 'answermesh'),
                        className: 'tab-freshness',
                    },
                    {
                        name: 'links',
                        title: __('Sitewide Link Equity & Graph', 'answermesh'),
                        className: 'tab-links',
                    },
                    {
                        name: 'opportunities',
                        title: __('Smart Anchor Opportunities', 'answermesh'),
                        className: 'tab-opportunities',
                    },
                ]}
            >
                {(tab) => {
                    if (tab.name === 'freshness') {
                        return (
                            <div className="answermesh-tab-content">
                                <div className="answermesh-filter-row">
                                    <h3>{__('Content Freshness Audit', 'answermesh')}</h3>
                                    <div className="filter-select">
                                        <SelectControl
                                            label={__('Filter Tier:', 'answermesh')}
                                            value={tierFilter}
                                            options={[
                                                { label: __('All Tiers', 'answermesh'), value: 'ALL' },
                                                { label: __('Critical (>365 days)', 'answermesh'), value: 'CRITICAL' },
                                                { label: __('Decaying (180-365 days)', 'answermesh'), value: 'DECAYING' },
                                                { label: __('Aging (60-180 days)', 'answermesh'), value: 'AGING' },
                                                { label: __('Fresh (<60 days)', 'answermesh'), value: 'FRESH' },
                                            ]}
                                            onChange={(val) => setTierFilter(val)}
                                        />
                                    </div>
                                </div>

                                <table className="answermesh-table widefat striped">
                                    <thead>
                                        <tr>
                                            <th>{__('Article Title', 'answermesh')}</th>
                                            <th>{__('Freshness Score', 'answermesh')}</th>
                                            <th>{__('Decay Status', 'answermesh')}</th>
                                            <th>{__('Last Modified', 'answermesh')}</th>
                                            <th style={{ textAlign: 'right' }}>{__('Action', 'answermesh')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredInventory.map((item) => (
                                            <tr key={item.post_id}>
                                                <td>
                                                    <strong>{item.title}</strong>
                                                </td>
                                                <td>
                                                    <span className="freshness-badge-num">{item.freshness_score}%</span>
                                                </td>
                                                <td>
                                                    <span className={`decay-badge decay-${item.tier}`}>
                                                        {item.tier_label} ({item.days_modified} {__('days ago', 'answermesh')})
                                                    </span>
                                                </td>
                                                <td>{item.last_modified}</td>
                                                <td style={{ textAlign: 'right' }}>
                                                    <a
                                                        href={item.edit_url}
                                                        className="button button-small button-primary"
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        {__('Refresh in Editor →', 'answermesh')}
                                                    </a>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        );
                    }

                    if (tab.name === 'links') {
                        const nodes = linkGraph?.nodes || [];
                        return (
                            <div className="answermesh-tab-content">
                                <h3>{__('Internal Link Equity Distribution', 'answermesh')}</h3>
                                <p className="answermesh-text-muted">
                                    {__('Articles with 0 inbound links are invisible true orphans. Target 3-5 inbound links per important page.', 'answermesh')}
                                </p>

                                <table className="answermesh-table widefat striped">
                                    <thead>
                                        <tr>
                                            <th>{__('Article Title', 'answermesh')}</th>
                                            <th>{__('Inbound Links (In-Degree)', 'answermesh')}</th>
                                            <th>{__('Outbound Links (Out-Degree)', 'answermesh')}</th>
                                            <th>{__('Orphan Status', 'answermesh')}</th>
                                            <th style={{ textAlign: 'right' }}>{__('Action', 'answermesh')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {nodes.map((node) => (
                                            <tr key={node.post_id}>
                                                <td><strong>{node.title}</strong></td>
                                                <td>
                                                    <span className={`link-degree-num ${node.in_degree === 0 ? 'alert-red' : ''}`}>
                                                        {node.in_degree}
                                                    </span>
                                                </td>
                                                <td>{node.out_degree}</td>
                                                <td>
                                                    {node.in_degree === 0 ? (
                                                        <span className="decay-badge decay-critical">
                                                            {__('True Orphan (0 Inbound)', 'answermesh')}
                                                        </span>
                                                    ) : (
                                                        <span className="decay-badge decay-fresh">
                                                            {__('Connected', 'answermesh')}
                                                        </span>
                                                    )}
                                                </td>
                                                <td style={{ textAlign: 'right' }}>
                                                    <a
                                                        href={`post.php?post=${node.post_id}&action=edit`}
                                                        className="button button-small"
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        {__('Edit Article', 'answermesh')}
                                                    </a>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        );
                    }

                    if (tab.name === 'opportunities') {
                        return (
                            <div className="answermesh-tab-content">
                                <h3>{__('High-Value Anchor Text Opportunities', 'answermesh')}</h3>
                                <p className="answermesh-text-muted">
                                    {__('Natural unlinked mentions across published posts that can be linked to pillars or cluster articles:', 'answermesh')}
                                </p>

                                {linkOpportunities.length === 0 ? (
                                    <Card className="answermesh-empty-card">
                                        <CardBody>
                                            <p>{__('No unlinked anchor opportunities detected at this time. Create more cluster content to expand opportunities.', 'answermesh')}</p>
                                        </CardBody>
                                    </Card>
                                ) : (
                                    <div className="opportunities-list">
                                        {linkOpportunities.map((opp, idx) => (
                                            <div key={idx} className="opportunity-card">
                                                <div className="opp-header">
                                                    <div>
                                                        <span className="opp-source">{opp.source_title}</span>
                                                        <span className="opp-arrow"> ➔ </span>
                                                        <span className="opp-target"><strong>{opp.target_title}</strong></span>
                                                    </div>
                                                    <span className="opp-anchor-pill">anchor: "{opp.anchor}"</span>
                                                </div>
                                                <div className="opp-snippet">
                                                    <em>{opp.context_snippet}</em>
                                                </div>
                                                <div className="opp-actions">
                                                    <Button
                                                        variant="secondary"
                                                        isSmall
                                                        onClick={() => {
                                                            navigator.clipboard.writeText(`<a href="${opp.target_url}">${opp.anchor}</a>`);
                                                            setNotice({
                                                                status: 'success',
                                                                message: __('Copied HTML link to clipboard!', 'answermesh'),
                                                            });
                                                        }}
                                                    >
                                                        {__('Copy HTML Link', 'answermesh')}
                                                    </Button>
                                                    <a
                                                        href={`post.php?post=${opp.source_post_id}&action=edit`}
                                                        className="button button-small"
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        {__('Open Source in Editor →', 'answermesh')}
                                                    </a>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    }

                    return null;
                }}
            </TabPanel>
        </div>
    );
}
