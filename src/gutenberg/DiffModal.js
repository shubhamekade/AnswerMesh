/**
 * Human-in-the-Loop Diff Approval Modal.
 * Protects editorial content: AI proposals require explicit review and approval.
 */

import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function DiffModal({
    isOpen,
    onClose,
    onApprove,
    originalContent,
    proposedContent,
    title,
    actionType,
    isApplying,
}) {
    if (!isOpen) return null;

    return (
        <Modal
            title={title || __('Review AI Content Proposal', 'answermesh')}
            onRequestClose={onClose}
            className="answermesh-diff-modal"
        >
            <div className="answermesh-diff-container">
                <div className="answermesh-diff-notice">
                    <p>
                        <strong>{__('Human-in-the-Loop Verification: ', 'answermesh')}</strong>
                        {__('Verify proposed content for factual accuracy before inserting into your article.', 'answermesh')}
                    </p>
                </div>

                <div className="answermesh-diff-columns">
                    {originalContent && (
                        <div className="answermesh-diff-col original-col">
                            <span className="diff-col-title">{__('Original Section / Context', 'answermesh')}</span>
                            <div className="diff-content-box original-box">
                                <pre>{originalContent}</pre>
                            </div>
                        </div>
                    )}

                    <div className="answermesh-diff-col proposed-col">
                        <span className="diff-col-title">{__('Proposed Addition / Change', 'answermesh')}</span>
                        <div className="diff-content-box proposed-box">
                            <div
                                className="diff-rendered-html"
                                dangerouslySetInnerHTML={{ __html: proposedContent }}
                            />
                        </div>
                    </div>
                </div>

                <div className="answermesh-modal-actions">
                    <Button variant="secondary" onClick={onClose} disabled={isApplying}>
                        {__('Discard / Cancel', 'answermesh')}
                    </Button>
                    <Button
                        variant="primary"
                        onClick={onApprove}
                        isBusy={isApplying}
                        disabled={isApplying}
                    >
                        {isApplying ? __('Inserting...', 'answermesh') : __('✓ Approve & Insert Into Editor', 'answermesh')}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
