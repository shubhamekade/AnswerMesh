/**
 * Topic Clusters Explorer & Management View.
 *
 * @package AnswerMesh
 */

import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    Card,
    CardHeader,
    CardBody,
    Modal,
    TextControl,
    SelectControl,
    Spinner,
    Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default function ClustersView() {
    const [clusters, setClusters] = useState([]);
    const [orphans, setOrphans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [discovering, setDiscovering] = useState(false);
    const [discovered, setDiscovered] = useState([]);
    const [notice, setNotice] = useState(null);
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);
    const [activeCluster, setActiveCluster] = useState(null);

    // Form states
    const [newClusterName, setNewClusterName] = useState('');
    const [selectedPillarId, setSelectedPillarId] = useState(0);
    const [assignPostId, setAssignPostId] = useState(0);

    const loadData = async () => {
        setLoading(true);
        try {
            const [clusterRes, orphanRes] = await Promise.all([
                apiFetch({ path: '/aiseo/v1/clusters' }),
                apiFetch({ path: '/aiseo/v1/clusters/orphans' }),
            ]);

            setClusters(clusterRes.clusters || []);
            setOrphans(orphanRes.orphans || []);
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to load topic clusters.', 'answermesh'),
            });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, []);

    const handleCreateCluster = async () => {
        if (!newClusterName.trim()) {
            return;
        }

        try {
            await apiFetch({
                path: '/aiseo/v1/clusters',
                method: 'POST',
                data: {
                    name: newClusterName,
                    pillar_post_id: parseInt(selectedPillarId, 10) || 0,
                },
            });

            setIsCreateModalOpen(false);
            setNewClusterName('');
            setSelectedPillarId(0);
            setNotice({
                status: 'success',
                message: __('Topic cluster created successfully.', 'answermesh'),
            });
            loadData();
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to create cluster.', 'answermesh'),
            });
        }
    };

    const handleDeleteCluster = async (id) => {
        if (!confirm(__('Are you sure you want to delete this topic cluster? Member posts will not be deleted.', 'answermesh'))) {
            return;
        }

        try {
            await apiFetch({
                path: `/aiseo/v1/clusters/${id}`,
                method: 'DELETE',
            });
            setNotice({
                status: 'success',
                message: __('Topic cluster deleted.', 'answermesh'),
            });
            loadData();
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to delete cluster.', 'answermesh'),
            });
        }
    };

    const handleAssignPost = async () => {
        if (!activeCluster || !assignPostId) return;

        try {
            const currentPosts = activeCluster.posts || [];
            const orphanObj = orphans.find((o) => o.post_id === parseInt(assignPostId, 10));

            const updatedPosts = [
                ...currentPosts,
                {
                    post_id: parseInt(assignPostId, 10),
                    role: 'cluster_support',
                    title: orphanObj ? orphanObj.title : __('Assigned Post', 'answermesh'),
                },
            ];

            await apiFetch({
                path: `/aiseo/v1/clusters/${activeCluster.id}`,
                method: 'PUT',
                data: { posts: updatedPosts },
            });

            setIsAssignModalOpen(false);
            setActiveCluster(null);
            setAssignPostId(0);
            setNotice({
                status: 'success',
                message: __('Post assigned to cluster.', 'answermesh'),
            });
            loadData();
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to assign post.', 'answermesh'),
            });
        }
    };

    const handleAutoDiscover = async () => {
        setDiscovering(true);
        try {
            const res = await apiFetch({
                path: '/aiseo/v1/clusters/auto-discover',
                method: 'POST',
            });
            setDiscovered(res.discovered || []);
            if ((res.discovered || []).length === 0) {
                setNotice({
                    status: 'info',
                    message: __('No new clusters discovered. Ensure you have published posts with shared categories or entities.', 'answermesh'),
                });
            }
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Auto-discovery failed.', 'answermesh'),
            });
        } finally {
            setDiscovering(false);
        }
    };

    const handleAdoptDiscovered = async (item) => {
        try {
            await apiFetch({
                path: '/aiseo/v1/clusters',
                method: 'POST',
                data: {
                    name: item.name,
                    pillar_post_id: item.pillar_post_id,
                    posts: item.candidate_posts.map((cp) => ({
                        post_id: cp.post_id,
                        role: 'cluster_support',
                        title: cp.title,
                    })),
                },
            });

            setDiscovered(discovered.filter((d) => d.name !== item.name));
            setNotice({
                status: 'success',
                message: sprintf(__('Adopted cluster "%s".', 'answermesh'), item.name),
            });
            loadData();
        } catch (err) {
            setNotice({
                status: 'error',
                message: err.message || __('Failed to adopt cluster.', 'answermesh'),
            });
        }
    };

    // Calculate aggregated stats
    const totalClusters = clusters.length;
    const totalClusteredPosts = clusters.reduce((acc, c) => {
        const pillarCount = c.pillar_post_id > 0 ? 1 : 0;
        return acc + pillarCount + (c.posts?.length || 0);
    }, 0);
    const avgDepth = totalClusters > 0
        ? Math.round(clusters.reduce((acc, c) => acc + (c.coverage_score || 0), 0) / totalClusters)
        : 0;

    if (loading) {
        return (
            <div className="answermesh-loading-center">
                <Spinner />
                <p>{__('Loading topic clusters...', 'answermesh')}</p>
            </div>
        );
    }

    return (
        <div className="answermesh-clusters-view">
            {notice && (
                <Notice status={notice.status} onDismiss={() => setNotice(null)} className="answermesh-notice">
                    {notice.message}
                </Notice>
            )}

            {/* Metrics Header */}
            <div className="answermesh-metric-strip">
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('Total Clusters', 'answermesh')}</span>
                    <span className="metric-value">{totalClusters}</span>
                </div>
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('Clustered Posts', 'answermesh')}</span>
                    <span className="metric-value">{totalClusteredPosts}</span>
                </div>
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('Orphan Posts', 'answermesh')}</span>
                    <span className="metric-value alert-orange">{orphans.length}</span>
                </div>
                <div className="answermesh-metric-card">
                    <span className="metric-label">{__('Avg Topical Depth', 'answermesh')}</span>
                    <span className="metric-value">{avgDepth}%</span>
                </div>
            </div>

            {/* Action Bar */}
            <div className="answermesh-action-bar">
                <div>
                    <h2>{__('Semantic Topic Clusters', 'answermesh')}</h2>
                    <p className="answermesh-text-muted">
                        {__('Organize content into Pillar and Supporting Cluster relationships to establish undeniable topical authority for AI search.', 'answermesh')}
                    </p>
                </div>
                <div className="action-buttons">
                    <Button
                        variant="secondary"
                        onClick={handleAutoDiscover}
                        isBusy={discovering}
                        disabled={discovering}
                    >
                        {discovering ? __('Discovering...', 'answermesh') : __('Auto-Discover Clusters', 'answermesh')}
                    </Button>
                    <Button
                        variant="primary"
                        onClick={() => setIsCreateModalOpen(true)}
                    >
                        {__('+ New Topic Cluster', 'answermesh')}
                    </Button>
                </div>
            </div>

            {/* Discovered Preview Drawer */}
            {discovered.length > 0 && (
                <div className="answermesh-discovered-box">
                    <h3>{__('Discovered Cluster Opportunities', 'answermesh')}</h3>
                    <p className="answermesh-text-muted">
                        {__('The engine identified natural content clusters based on shared categories and entity mentions:', 'answermesh')}
                    </p>
                    <div className="discovered-list">
                        {discovered.map((item, idx) => (
                            <div key={idx} className="discovered-item">
                                <div>
                                    <strong>{item.name}</strong> — {__('Nominated Pillar:', 'answermesh')} <em>{item.pillar_title}</em>
                                    <span className="badge-count">{item.total_posts} {__('posts', 'answermesh')}</span>
                                </div>
                                <Button
                                    variant="secondary"
                                    isSmall
                                    onClick={() => handleAdoptDiscovered(item)}
                                >
                                    {__('Adopt Cluster', 'answermesh')}
                                </Button>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Clusters Grid */}
            {clusters.length === 0 ? (
                <Card className="answermesh-empty-card">
                    <CardBody>
                        <p className="empty-title">{__('No Topic Clusters Configured Yet', 'answermesh')}</p>
                        <p className="answermesh-text-muted">
                            {__('Click "Auto-Discover Clusters" to group your existing posts automatically, or create your first cluster manually.', 'answermesh')}
                        </p>
                    </CardBody>
                </Card>
            ) : (
                <div className="answermesh-clusters-grid">
                    {clusters.map((cluster) => {
                        const posts = cluster.posts || [];
                        const score = cluster.coverage_score || 0;
                        const subtopics = cluster.subtopics || [];

                        return (
                            <Card key={cluster.id} className="answermesh-cluster-card">
                                <CardHeader className="cluster-header">
                                    <div>
                                        <h3 className="cluster-title">{cluster.name}</h3>
                                        <span className="cluster-slug">slug: {cluster.slug}</span>
                                    </div>
                                    <div className="cluster-score-badge">
                                        <span className="score-num">{score}%</span>
                                        <span className="score-caption">{__('Depth', 'answermesh')}</span>
                                    </div>
                                </CardHeader>
                                <CardBody>
                                    {/* Pillar Post Highlight */}
                                    <div className="cluster-pillar-box">
                                        <div className="pillar-badge">★ {__('PILLAR PAGE', 'answermesh')}</div>
                                        <div className="pillar-title">
                                            {cluster.pillar_title || __('No pillar assigned yet', 'answermesh')}
                                        </div>
                                    </div>

                                    {/* Supporting Articles */}
                                    <div className="cluster-members-section">
                                        <h4>{__('Supporting Cluster Articles', 'answermesh')} ({posts.length})</h4>
                                        {posts.length === 0 ? (
                                            <p className="no-members">{__('No supporting articles assigned.', 'answermesh')}</p>
                                        ) : (
                                            <ul className="members-list">
                                                {posts.map((p, pidx) => (
                                                    <li key={pidx} className="member-item">
                                                        <span className="member-bullet">•</span>
                                                        <span className="member-title">{p.title || `Post #${p.post_id}`}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>

                                    {/* Subtopics / Gaps */}
                                    {subtopics.length > 0 && (
                                        <div className="cluster-subtopics-box">
                                            <span className="subtopic-label">{__('Covered Subtopics:', 'answermesh')}</span>
                                            <div className="subtopic-tags">
                                                {subtopics.map((st, sidx) => (
                                                    <span key={sidx} className="subtopic-tag">{st}</span>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Card Footer Actions */}
                                    <div className="cluster-footer-actions">
                                        <Button
                                            variant="secondary"
                                            isSmall
                                            onClick={() => {
                                                setActiveCluster(cluster);
                                                setIsAssignModalOpen(true);
                                            }}
                                        >
                                            {__('+ Assign Post', 'answermesh')}
                                        </Button>
                                        <Button
                                            variant="link"
                                            isDestructive
                                            isSmall
                                            onClick={() => handleDeleteCluster(cluster.id)}
                                        >
                                            {__('Delete', 'answermesh')}
                                        </Button>
                                    </div>
                                </CardBody>
                            </Card>
                        );
                    })}
                </div>
            )}

            {/* Orphan Posts Section */}
            {orphans.length > 0 && (
                <div className="answermesh-orphans-section">
                    <h3>{__('Orphan Posts Queue', 'answermesh')} ({orphans.length})</h3>
                    <p className="answermesh-text-muted">
                        {__('These published articles are not associated with any topic cluster. Assign them to establish semantic links.', 'answermesh')}
                    </p>
                    <div className="orphans-table-wrapper">
                        <table className="answermesh-table">
                            <thead>
                                <tr>
                                    <th>{__('Post Title', 'answermesh')}</th>
                                    <th>{__('Category', 'answermesh')}</th>
                                    <th>{__('Suggested Cluster', 'answermesh')}</th>
                                    <th>{__('Action', 'answermesh')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orphans.map((orphan) => (
                                    <tr key={orphan.post_id}>
                                        <td><strong>{orphan.title}</strong></td>
                                        <td>{orphan.category}</td>
                                        <td>
                                            {orphan.suggested_cluster_name ? (
                                                <span className="badge-suggested">{orphan.suggested_cluster_name}</span>
                                            ) : (
                                                <span className="text-muted">—</span>
                                            )}
                                        </td>
                                        <td>
                                            {orphan.suggested_cluster_id > 0 && (
                                                <Button
                                                    variant="secondary"
                                                    isSmall
                                                    onClick={async () => {
                                                        const targetCluster = clusters.find((c) => c.id === orphan.suggested_cluster_id);
                                                        if (targetCluster) {
                                                            const updated = [
                                                                ...(targetCluster.posts || []),
                                                                {
                                                                    post_id: orphan.post_id,
                                                                    role: 'cluster_support',
                                                                    title: orphan.title,
                                                                },
                                                            ];
                                                            await apiFetch({
                                                                path: `/aiseo/v1/clusters/${targetCluster.id}`,
                                                                method: 'PUT',
                                                                data: { posts: updated },
                                                            });
                                                            setNotice({
                                                                status: 'success',
                                                                message: sprintf(__('Assigned "%s" to %s.', 'answermesh'), orphan.title, targetCluster.name),
                                                            });
                                                            loadData();
                                                        }
                                                    }}
                                                >
                                                    {__('Quick Assign', 'answermesh')}
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Create Cluster Modal */}
            {isCreateModalOpen && (
                <Modal
                    title={__('Create New Topic Cluster', 'answermesh')}
                    onRequestClose={() => setIsCreateModalOpen(false)}
                >
                    <div className="answermesh-modal-content">
                        <TextControl
                            label={__('Cluster Name', 'answermesh')}
                            value={newClusterName}
                            onChange={(val) => setNewClusterName(val)}
                            help={__('e.g., WordPress Performance, Local SEO Strategies, Cambridge Curriculum', 'answermesh')}
                        />

                        <SelectControl
                            label={__('Select Pillar Post (Cornerstone Content)', 'answermesh')}
                            value={selectedPillarId}
                            options={[
                                { label: __('-- Select a pillar post --', 'answermesh'), value: 0 },
                                ...orphans.map((o) => ({
                                    label: o.title,
                                    value: o.post_id,
                                })),
                            ]}
                            onChange={(val) => setSelectedPillarId(parseInt(val, 10))}
                            help={__('The authoritative anchor article that introduces the core topic.', 'answermesh')}
                        />

                        <div className="modal-actions">
                            <Button variant="secondary" onClick={() => setIsCreateModalOpen(false)}>
                                {__('Cancel', 'answermesh')}
                            </Button>
                            <Button variant="primary" onClick={handleCreateCluster}>
                                {__('Save Cluster', 'answermesh')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}

            {/* Assign Post Modal */}
            {isAssignModalOpen && activeCluster && (
                <Modal
                    title={sprintf(__('Assign Post to "%s"', 'answermesh'), activeCluster.name)}
                    onRequestClose={() => setIsAssignModalOpen(false)}
                >
                    <div className="answermesh-modal-content">
                        <SelectControl
                            label={__('Choose Post to Assign', 'answermesh')}
                            value={assignPostId}
                            options={[
                                { label: __('-- Select an orphan post --', 'answermesh'), value: 0 },
                                ...orphans.map((o) => ({
                                    label: o.title,
                                    value: o.post_id,
                                })),
                            ]}
                            onChange={(val) => setAssignPostId(parseInt(val, 10))}
                        />

                        <div className="modal-actions">
                            <Button variant="secondary" onClick={() => setIsAssignModalOpen(false)}>
                                {__('Cancel', 'answermesh')}
                            </Button>
                            <Button variant="primary" onClick={handleAssignPost}>
                                {__('Assign to Cluster', 'answermesh')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
}
