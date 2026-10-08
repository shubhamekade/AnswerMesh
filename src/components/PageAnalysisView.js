/**
 * Page Inventory & Analysis View.
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function PageAnalysisView() {
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [analyzingId, setAnalyzingId] = useState(null);
    const [notice, setNotice] = useState(null);

    const loadPosts = () => {
        setLoading(true);
        // Fetch posts from WP REST API or custom endpoint
        apiFetch({ path: '/wp/v2/posts?per_page=20&_embed=true' })
            .then((res) => {
                setPosts(res || []);
                setLoading(false);
            })
            .catch(() => {
                // Fallback to empty if wp/v2 not available
                setPosts([]);
                setLoading(false);
            });
    };

    useEffect(() => {
        loadPosts();
    }, []);

    const handleAnalyze = (postId) => {
        setAnalyzingId(postId);
        setNotice(null);

        apiFetch({
            path: `/aiseo/v1/posts/${postId}/analyze`,
            method: 'POST',
        })
            .then((res) => {
                setAnalyzingId(null);
                setNotice({
                    type: 'success',
                    message: res.message || __('Post analyzed successfully.', 'answermesh'),
                });
            })
            .catch((err) => {
                setAnalyzingId(null);
                setNotice({
                    type: 'error',
                    message: err.message || __('Failed to analyze post.', 'answermesh'),
                });
            });
    };

    if (loading) {
        return (
            <div className="answermesh-loading-panel">
                <Spinner />
                <p>{__('Loading content inventory...', 'answermesh')}</p>
            </div>
        );
    }

    return (
        <div className="answermesh-pages-view">
            <div className="answermesh-section-header">
                <div>
                    <h2>{__('Page & Content Inventory', 'answermesh')}</h2>
                    <p className="answermesh-feed-subtitle">
                        {__('Run on-demand audits and inspect visibility scores for published articles.', 'answermesh')}
                    </p>
                </div>
            </div>

            {notice && (
                <Notice status={notice.type} isDismissible onDismiss={() => setNotice(null)}>
                    {notice.message}
                </Notice>
            )}

            {posts.length === 0 ? (
                <div className="answermesh-empty-card">
                    <p>{__('No published posts found to analyze.', 'answermesh')}</p>
                </div>
            ) : (
                <table className="answermesh-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style={{ width: '40%' }}>{__('Title', 'answermesh')}</th>
                            <th>{__('Date', 'answermesh')}</th>
                            <th>{__('Status', 'answermesh')}</th>
                            <th style={{ textAlign: 'right' }}>{__('Actions', 'answermesh')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {posts.map((post) => (
                            <tr key={post.id}>
                                <td>
                                    <strong>{post.title?.rendered || __('(Untitled)', 'answermesh')}</strong>
                                </td>
                                <td>{new Date(post.date).toLocaleDateString()}</td>
                                <td>
                                    <span className="answermesh-badge-small">{post.status}</span>
                                </td>
                                <td style={{ textAlign: 'right' }}>
                                    <Button
                                        variant="secondary"
                                        isSmall
                                        onClick={() => handleAnalyze(post.id)}
                                        isBusy={analyzingId === post.id}
                                        disabled={analyzingId === post.id}
                                    >
                                        {analyzingId === post.id ? __('Analyzing...', 'answermesh') : __('Run Audit', 'answermesh')}
                                    </Button>
                                    {' '}
                                    <a
                                        href={`post.php?post=${post.id}&action=edit`}
                                        className="button button-small"
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        {__('Edit', 'answermesh')}
                                    </a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
