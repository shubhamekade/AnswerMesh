/**
 * Business Knowledge Base Management Component.
 * Allows site owners to manage verified entities and schema attributes.
 */

import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    Spinner,
    Notice,
    Modal,
    TextControl,
    TextareaControl,
    SelectControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const ENTITY_TYPES = [
    { label: __('Organization', 'answermesh'), value: 'Organization' },
    { label: __('Person / Founder', 'answermesh'), value: 'Person' },
    { label: __('Service / Offering', 'answermesh'), value: 'Service' },
    { label: __('Product', 'answermesh'), value: 'Product' },
    { label: __('Local Business / Location', 'answermesh'), value: 'LocalBusiness' },
];

export default function KnowledgeBaseView() {
    const [entities, setEntities] = useState([]);
    const [loading, setLoading] = useState(true);
    const [notice, setNotice] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingEntity, setEditingEntity] = useState(null);
    const [saving, setSaving] = useState(false);

    // Form state
    const [formName, setFormName] = useState('');
    const [formType, setFormType] = useState('Organization');
    const [formDesc, setFormDesc] = useState('');
    const [formSameAs, setFormSameAs] = useState('');

    const loadEntities = () => {
        setLoading(true);
        apiFetch({ path: '/aiseo/v1/entities' })
            .then((res) => {
                setEntities(res.data || []);
                setLoading(false);
            })
            .catch((err) => {
                setNotice({ type: 'error', message: err.message || __('Failed to load entities.', 'answermesh') });
                setLoading(false);
            });
    };

    useEffect(() => {
        loadEntities();
    }, []);

    const openAddModal = () => {
        setEditingEntity(null);
        setFormName('');
        setFormType('Organization');
        setFormDesc('');
        setFormSameAs('');
        setIsModalOpen(true);
    };

    const openEditModal = (entity) => {
        setEditingEntity(entity);
        setFormName(entity.entity_name || '');
        setFormType(entity.entity_type || 'Organization');
        setFormDesc(entity.description || '');
        setFormSameAs(entity.same_as || '');
        setIsModalOpen(true);
    };

    const handleSave = () => {
        if (!formName.trim()) {
            setNotice({ type: 'error', message: __('Entity name is required.', 'answermesh') });
            return;
        }

        setSaving(true);
        const payload = {
            id: editingEntity?.id,
            entity_name: formName.trim(),
            entity_type: formType,
            description: formDesc.trim(),
            same_as: formSameAs.trim(),
        };

        apiFetch({
            path: '/aiseo/v1/entities',
            method: 'POST',
            data: payload,
        })
            .then((res) => {
                setSaving(false);
                setIsModalOpen(false);
                setNotice({ type: 'success', message: res.message || __('Entity saved successfully.', 'answermesh') });
                loadEntities();
            })
            .catch((err) => {
                setSaving(false);
                setNotice({ type: 'error', message: err.message || __('Failed to save entity.', 'answermesh') });
            });
    };

    const handleDelete = (id) => {
        if (!window.confirm(__('Are you sure you want to delete this verified entity?', 'answermesh'))) {
            return;
        }

        apiFetch({
            path: `/aiseo/v1/entities/${id}`,
            method: 'DELETE',
        })
            .then((res) => {
                setNotice({ type: 'success', message: res.message || __('Entity deleted.', 'answermesh') });
                loadEntities();
            })
            .catch((err) => {
                setNotice({ type: 'error', message: err.message || __('Failed to delete entity.', 'answermesh') });
            });
    };

    if (loading) {
        return (
            <div className="answermesh-loading-panel">
                <Spinner />
                <p>{__('Loading Business Knowledge Base entities...', 'answermesh')}</p>
            </div>
        );
    }

    return (
        <div className="answermesh-knowledge-view">
            <div className="answermesh-section-header">
                <div>
                    <h2>{__('Business Knowledge Base', 'answermesh')}</h2>
                    <p className="answermesh-feed-subtitle">
                        {__('Verified site facts injected into Schema graphs and LLM prompts to prevent hallucination.', 'answermesh')}
                    </p>
                </div>
                <Button variant="primary" onClick={openAddModal}>
                    {__('+ Add Verified Entity', 'answermesh')}
                </Button>
            </div>

            {notice && (
                <Notice status={notice.type} isDismissible onDismiss={() => setNotice(null)}>
                    {notice.message}
                </Notice>
            )}

            {entities.length === 0 ? (
                <div className="answermesh-empty-card">
                    <p>{__('No verified entities yet. Add your Organization or Key Persons to ground AI answers.', 'answermesh')}</p>
                    <Button variant="secondary" onClick={openAddModal}>
                        {__('Add First Entity', 'answermesh')}
                    </Button>
                </div>
            ) : (
                <div className="answermesh-entity-grid">
                    {entities.map((entity) => (
                        <div key={entity.id} className="answermesh-entity-card">
                            <div className="answermesh-entity-card-header">
                                <span className="answermesh-entity-type-badge">{entity.entity_type}</span>
                                <div className="answermesh-entity-actions">
                                    <Button variant="tertiary" isSmall onClick={() => openEditModal(entity)}>
                                        {__('Edit', 'answermesh')}
                                    </Button>
                                    <Button variant="tertiary" isDestructive isSmall onClick={() => handleDelete(entity.id)}>
                                        {__('Delete', 'answermesh')}
                                    </Button>
                                </div>
                            </div>
                            <h3 className="answermesh-entity-name">{entity.entity_name}</h3>
                            {entity.description && (
                                <p className="answermesh-entity-desc">{entity.description}</p>
                            )}
                            {entity.same_as && (
                                <div className="answermesh-entity-sameas">
                                    <span className="attr-label">{__('Verified URL:', 'answermesh')}</span>
                                    <a href={entity.same_as} target="_blank" rel="noreferrer" className="attr-link">
                                        {entity.same_as}
                                    </a>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {isModalOpen && (
                <Modal
                    title={editingEntity ? __('Edit Verified Entity', 'answermesh') : __('Add Verified Entity', 'answermesh')}
                    onRequestClose={() => setIsModalOpen(false)}
                    className="answermesh-modal"
                >
                    <div className="answermesh-form-group">
                        <TextControl
                            label={__('Entity Name', 'answermesh')}
                            value={formName}
                            onChange={setFormName}
                            placeholder="e.g. AnswerMesh Inc, Jane Doe"
                            required
                        />

                        <SelectControl
                            label={__('Entity Type', 'answermesh')}
                            value={formType}
                            options={ENTITY_TYPES}
                            onChange={setFormType}
                        />

                        <TextareaControl
                            label={__('Verified Description / Core Facts', 'answermesh')}
                            value={formDesc}
                            onChange={setFormDesc}
                            help={__('Provide ground truth facts. The AI assistant uses this to formulate answers.', 'answermesh')}
                            rows={4}
                        />

                        <TextControl
                            label={__('Official / Social Profile URL (sameAs)', 'answermesh')}
                            value={formSameAs}
                            onChange={setFormSameAs}
                            placeholder="https://www.linkedin.com/company/example"
                        />

                        <div className="answermesh-modal-actions">
                            <Button variant="secondary" onClick={() => setIsModalOpen(false)}>
                                {__('Cancel', 'answermesh')}
                            </Button>
                            <Button
                                variant="primary"
                                onClick={handleSave}
                                isBusy={saving}
                                disabled={saving}
                            >
                                {saving ? __('Saving...', 'answermesh') : __('Save Entity', 'answermesh')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
}
