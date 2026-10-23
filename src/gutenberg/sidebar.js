/**
 * AnswerMesh Gutenberg Sidebar Component.
 */

import { useState, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
    PanelBody,
    PanelRow,
    Button,
    Spinner,
    Notice,
    Card,
    CardBody,
} from '@wordpress/components';
import { createBlock } from '@wordpress/blocks';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import DiffModal from './DiffModal';

export default function AnswerMeshSidebar() {
    const postId = useSelect((select) => select('core/editor').getCurrentPostId(), []);
    const postTitle = useSelect((select) => select('core/editor').getEditedPostAttribute('title'), []);

    const { insertBlocks } = useDispatch('core/block-editor');

    const [analysis, setAnalysis] = useState(null);
    const [loading, setLoading] = useState(false);
    const [notice, setNotice] = useState(null);

    // AI Generation states
    const [generatingAnswer, setGeneratingAnswer] = useState(false);
    const [generatingFaq, setGeneratingFaq] = useState(false);

    // Diff Modal state
    const [diffModalOpen, setDiffModalOpen] = useState(false);
    const [diffProposal, setDiffProposal] = useState(null);
    const [isApplying, setIsApplying] = useState(false);

    const loadPostAnalysis = () => {
        if (!postId) return;
        setLoading(true);

        apiFetch({ path: `/aiseo/v1/posts/${postId}/analysis` })
            .then((res) => {
                setAnalysis(res.data);
                setLoading(false);
            })
            .catch((err) => {
                setNotice({
                    type: 'error',
                    message: err.message || __('Failed to load post diagnostic scores.', 'answermesh'),
                });
                setLoading(false);
            });
    };

    useEffect(() => {
        if (postId) {
            loadPostAnalysis();
        }
    }, [postId]);

    const handleGenerateAnswer = (questionText = '') => {
        setGeneratingAnswer(true);
        setNotice(null);

        apiFetch({
            path: `/aiseo/v1/posts/${postId}/generate-answer`,
            method: 'POST',
            data: {
                question: questionText || `What is ${postTitle}?`,
            },
        })
            .then((res) => {
                setGeneratingAnswer(false);
                const html = res.data?.html || `<div class="answermesh-answer-block"><p><strong>Quick Answer:</strong> ${res.data?.answer_text || ''}</p></div>`;
                setDiffProposal({
                    title: __('Review Generated Direct Answer Block', 'answermesh'),
                    proposedContent: html,
                    originalContent: '',
                    blockContent: html,
                });
                setDiffModalOpen(true);
            })
            .catch((err) => {
                setGeneratingAnswer(false);
                setNotice({
                    type: 'error',
                    message: err.message || __('Failed to generate direct answer.', 'answermesh'),
                });
            });
    };

    const handleGenerateFaq = () => {
        setGeneratingFaq(true);
        setNotice(null);

        apiFetch({
            path: `/aiseo/v1/posts/${postId}/generate-faq`,
            method: 'POST',
            data: {
                count: 3,
            },
        })
            .then((res) => {
                setGeneratingFaq(false);
                const html = res.data?.html || (res.data?.faqs || []).map((faq) => `<details><summary><strong>${faq.question}</strong></summary><p>${faq.answer}</p></details>`).join('\n');
                setDiffProposal({
                    title: __('Review Generated FAQ Section', 'answermesh'),
                    proposedContent: html,
                    originalContent: '',
                    blockContent: html,
                });
                setDiffModalOpen(true);
            })
            .catch((err) => {
                setGeneratingFaq(false);
                setNotice({
                    type: 'error',
                    message: err.message || __('Failed to generate FAQ section.', 'answermesh'),
                });
            });
    };

    const handleApproveProposal = () => {
        if (!diffProposal?.blockContent) return;
        setIsApplying(true);

        try {
            // Insert HTML block into editor
            const newBlock = createBlock('core/html', {
                content: diffProposal.blockContent,
            });

            insertBlocks(newBlock);
            setIsApplying(false);
            setDiffModalOpen(false);
            setDiffProposal(null);

            setNotice({
                type: 'success',
                message: __('Content block inserted into editor! Remember to save draft/update.', 'answermesh'),
            });

            // Re-run post analysis after small delay
            setTimeout(() => {
                loadPostAnalysis();
            }, 1000);
        } catch (err) {
            setIsApplying(false);
            setNotice({
                type: 'error',
                message: __('Failed to insert block into editor.', 'answermesh'),
            });
        }
    };

    const overallScore = Math.round(Number(analysis?.scores?.overall ?? 0));
    const questions = analysis?.questions || [];

    const getScoreColor = (val) => {
        if (val >= 80) return '#10b981';
        if (val >= 60) return '#3b82f6';
        if (val >= 40) return '#f59e0b';
        return '#ef4444';
    };

    return (
        <div className="answermesh-sidebar-content">
            {notice && (
                <Notice status={notice.type} isDismissible onDismiss={() => setNotice(null)}>
                    {notice.message}
                </Notice>
            )}

            {/* Score Overview Panel */}
            <PanelBody title={__('Visibility Score', 'answermesh')} initialOpen={true}>
                {loading ? (
                    <div style={{ textAlign: 'center', padding: '16px' }}>
                        <Spinner />
                        <p>{__('Auditing post...', 'answermesh')}</p>
                    </div>
                ) : (
                    <>
                        <div className="answermesh-sidebar-score-row">
                            <div
                                className="answermesh-sidebar-score-circle"
                                style={{ borderColor: getScoreColor(overallScore) }}
                            >
                                <span className="score-num">{overallScore}</span>
                                <span className="score-max">/100</span>
                            </div>
                            <div className="answermesh-sidebar-score-label">
                                <strong>{analysis?.rating || (overallScore >= 70 ? __('Healthy', 'answermesh') : __('Needs Attention', 'answermesh'))}</strong>
                                <p style={{ margin: '4px 0 0', color: '#646970', fontSize: '12px' }}>
                                    {__('Multi-layer AEO & GEO index.', 'answermesh')}
                                </p>
                            </div>
                        </div>

                        <div className="answermesh-sidebar-meters">
                            {[
                                { key: 'seo', label: __('SEO', 'answermesh'), val: analysis?.scores?.seo ?? 0 },
                                { key: 'aeo', label: __('AEO', 'answermesh'), val: analysis?.scores?.aeo ?? 0 },
                                { key: 'geo', label: __('GEO', 'answermesh'), val: analysis?.scores?.geo ?? 0 },
                                { key: 'entity', label: __('Entity', 'answermesh'), val: analysis?.scores?.entity ?? 0 },
                                { key: 'trust', label: __('Trust', 'answermesh'), val: analysis?.scores?.trust ?? 0 },
                            ].map((item) => (
                                <div key={item.key} className="sidebar-meter-row">
                                    <span className="meter-label">{item.label}</span>
                                    <div className="meter-track">
                                        <div
                                            className="meter-fill"
                                            style={{
                                                width: `${Math.round(item.val)}%`,
                                                backgroundColor: getScoreColor(item.val),
                                            }}
                                        />
                                    </div>
                                    <span className="meter-val">{Math.round(item.val)}%</span>
                                </div>
                            ))}
                        </div>

                        <Button
                            variant="secondary"
                            isSmall
                            onClick={loadPostAnalysis}
                            style={{ marginTop: '12px', width: '100%' }}
                        >
                            {__('Re-Audit Post', 'answermesh')}
                        </Button>
                    </>
                )}
            </PanelBody>

            {/* AI Assistant Actions Panel */}
            <PanelBody title={__('AI Search Assistant', 'answermesh')} initialOpen={true}>
                <p style={{ fontSize: '12px', color: '#646970', marginBottom: '12px' }}>
                    {__('Generate verified answer engines and FAQ blocks. Requires explicit diff approval.', 'answermesh')}
                </p>

                <div className="answermesh-sidebar-actions">
                    <Button
                        variant="primary"
                        onClick={() => handleGenerateAnswer()}
                        isBusy={generatingAnswer}
                        disabled={generatingAnswer}
                        style={{ width: '100%', marginBottom: '8px' }}
                    >
                        {generatingAnswer ? __('Generating...', 'answermesh') : __('+ Generate Direct Answer Block', 'answermesh')}
                    </Button>

                    <Button
                        variant="secondary"
                        onClick={handleGenerateFaq}
                        isBusy={generatingFaq}
                        disabled={generatingFaq}
                        style={{ width: '100%' }}
                    >
                        {generatingFaq ? __('Generating FAQs...', 'answermesh') : __('+ Generate FAQ Section', 'answermesh')}
                    </Button>
                </div>
            </PanelBody>

            {/* Question Coverage Panel */}
            <PanelBody title={__('Discovered Questions', 'answermesh')} initialOpen={false}>
                {questions.length === 0 ? (
                    <p style={{ fontSize: '12px', color: '#646970' }}>
                        {__('No explicit user questions detected in post headings. Consider adding conversational questions.', 'answermesh')}
                    </p>
                ) : (
                    <div className="answermesh-question-list">
                        {questions.map((q, idx) => (
                            <div key={idx} className="answermesh-question-item">
                                <div className="q-header">
                                    <span className={`q-status status-${(q.coverage_status || 'missing').toLowerCase()}`}>
                                        {q.coverage_status || 'Missing'}
                                    </span>
                                    <span className="q-intent">{q.intent || 'Informational'}</span>
                                </div>
                                <p className="q-text">{q.question_text}</p>
                                {q.coverage_status !== 'answered' && (
                                    <Button
                                        variant="link"
                                        isSmall
                                        onClick={() => handleGenerateAnswer(q.question_text)}
                                    >
                                        {__('Generate Answer →', 'answermesh')}
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </PanelBody>

            {/* Internal Link Opportunities Panel */}
            <PanelBody title={__('Internal Link Opportunities', 'answermesh')} initialOpen={true}>
                <p style={{ fontSize: '12px', color: '#646970', marginBottom: '10px' }}>
                    {__('High-value internal link recommendations to establish topical depth and resolve orphan content:', 'answermesh')}
                </p>
                <LinkOpportunitiesList postId={postId} />
            </PanelBody>

            {/* Diff Preview & Approval Modal */}
            <DiffModal
                isOpen={diffModalOpen}
                onClose={() => setDiffModalOpen(false)}
                onApprove={handleApproveProposal}
                title={diffProposal?.title}
                originalContent={diffProposal?.originalContent}
                proposedContent={diffProposal?.proposedContent}
                isApplying={isApplying}
            />
        </div>
    );
}

function LinkOpportunitiesList({ postId }) {
    const [opps, setOpps] = useState([]);
    const [loading, setLoading] = useState(true);
    const [copiedIdx, setCopiedIdx] = useState(null);

    useEffect(() => {
        if (!postId) return;
        setLoading(true);
        apiFetch({ path: `/aiseo/v1/links/opportunities?post_id=${postId}` })
            .then((res) => {
                setOpps(res.opportunities || []);
                setLoading(false);
            })
            .catch(() => {
                setOpps([]);
                setLoading(false);
            });
    }, [postId]);

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: '12px' }}>
                <Spinner />
            </div>
        );
    }

    if (opps.length === 0) {
        return (
            <p style={{ fontSize: '12px', color: '#646970' }}>
                {__('No link opportunities found for this post yet.', 'answermesh')}
            </p>
        );
    }

    return (
        <div className="answermesh-sidebar-opp-list">
            {opps.slice(0, 5).map((opp, idx) => (
                <div key={idx} className="answermesh-sidebar-opp-item">
                    <div className="opp-meta">
                        <span className="opp-target-title">
                            {__('Link to:', 'answermesh')} <strong>{opp.target_title}</strong>
                        </span>
                        <span className="opp-anchor-tag">"{opp.anchor}"</span>
                    </div>
                    <p className="opp-snippet-text">{opp.context_snippet}</p>
                    <div className="opp-btn-row">
                        <Button
                            variant="secondary"
                            isSmall
                            onClick={() => {
                                navigator.clipboard.writeText(`<a href="${opp.target_url}">${opp.anchor}</a>`);
                                setCopiedIdx(idx);
                                setTimeout(() => setCopiedIdx(null), 2000);
                            }}
                        >
                            {copiedIdx === idx ? __('Copied! ✓', 'answermesh') : __('Copy Link HTML', 'answermesh')}
                        </Button>
                    </div>
                </div>
            ))}
        </div>
    );
}
