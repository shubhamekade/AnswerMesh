/**
 * Settings & AI Configuration Component.
 */

import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    Spinner,
    Notice,
    SelectControl,
    TextControl,
    TextareaControl,
    ToggleControl,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function SettingsView() {
    const [settings, setSettings] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testingAi, setTestingAi] = useState(false);
    const [notice, setNotice] = useState(null);

    // Editable form state
    const [provider, setProvider] = useState('openai');
    const [apiKey, setApiKey] = useState('');
    const [openAiModel, setOpenAiModel] = useState('gpt-4o-mini');
    const [geminiModel, setGeminiModel] = useState('gemini-1.5-flash');
    const [brandVoice, setBrandVoice] = useState('');
    const [maxCrawlPages, setMaxCrawlPages] = useState(50);
    const [enableTelemetry, setEnableTelemetry] = useState(false);

    const loadSettings = () => {
        setLoading(true);
        apiFetch({ path: '/aiseo/v1/settings' })
            .then((res) => {
                const data = res.data || {};
                setSettings(data);
                setProvider(data.ai_provider || 'openai');
                setApiKey(data.api_key || '');
                setOpenAiModel(data.openai_model || 'gpt-4o-mini');
                setGeminiModel(data.gemini_model || 'gemini-1.5-flash');
                setBrandVoice(data.brand_voice || '');
                setMaxCrawlPages(data.max_crawl_pages || 50);
                setEnableTelemetry(Boolean(data.enable_telemetry));
                setLoading(false);
            })
            .catch((err) => {
                setNotice({ type: 'error', message: err.message || __('Failed to load settings.', 'answermesh') });
                setLoading(false);
            });
    };

    useEffect(() => {
        loadSettings();
    }, []);

    const handleSaveSettings = () => {
        setSaving(true);
        setNotice(null);

        const payload = {
            ai_provider: provider,
            api_key: apiKey,
            openai_model: openAiModel,
            gemini_model: geminiModel,
            brand_voice: brandVoice,
            max_crawl_pages: parseInt(maxCrawlPages, 10) || 50,
            enable_telemetry: enableTelemetry ? 1 : 0,
        };

        apiFetch({
            path: '/aiseo/v1/settings',
            method: 'POST',
            data: payload,
        })
            .then((res) => {
                setSaving(false);
                setNotice({ type: 'success', message: res.message || __('Settings saved successfully.', 'answermesh') });
                loadSettings();
            })
            .catch((err) => {
                setSaving(false);
                setNotice({ type: 'error', message: err.message || __('Failed to save settings.', 'answermesh') });
            });
    };

    const handleTestAiConnection = () => {
        setTestingAi(true);
        setNotice(null);

        apiFetch({
            path: '/aiseo/v1/ai/test-connection',
            method: 'POST',
            data: {
                provider,
                api_key: apiKey,
            },
        })
            .then((res) => {
                setTestingAi(false);
                setNotice({
                    type: res.data?.connected ? 'success' : 'warning',
                    message: res.message || (res.data?.connected ? __('AI Provider connection verified!', 'answermesh') : __('Connection failed.', 'answermesh')),
                });
            })
            .catch((err) => {
                setTestingAi(false);
                setNotice({
                    type: 'error',
                    message: err.message || __('AI connection test failed.', 'answermesh'),
                });
            });
    };

    if (loading) {
        return (
            <div className="answermesh-loading-panel">
                <Spinner />
                <p>{__('Loading plugin configuration...', 'answermesh')}</p>
            </div>
        );
    }

    return (
        <div className="answermesh-settings-view">
            <div className="answermesh-section-header">
                <div>
                    <h2>{__('AnswerMesh Configuration', 'answermesh')}</h2>
                    <p className="answermesh-feed-subtitle">
                        {__('Configure AI models, encryption credentials, brand voice rules, and crawler limits.', 'answermesh')}
                    </p>
                </div>
                <Button
                    variant="primary"
                    onClick={handleSaveSettings}
                    isBusy={saving}
                    disabled={saving}
                >
                    {saving ? __('Saving...', 'answermesh') : __('Save Settings', 'answermesh')}
                </Button>
            </div>

            {notice && (
                <Notice status={notice.type} isDismissible onDismiss={() => setNotice(null)}>
                    {notice.message}
                </Notice>
            )}

            <div className="answermesh-settings-grid">
                <div className="answermesh-settings-card">
                    <h3 className="answermesh-card-title">{__('AI Provider Integration', 'answermesh')}</h3>
                    <p className="answermesh-card-desc">
                        {__('Keys are encrypted using AES-256-CBC at rest. Keys are never exposed on the frontend.', 'answermesh')}
                    </p>

                    <SelectControl
                        label={__('Active AI Provider', 'answermesh')}
                        value={provider}
                        options={[
                            { label: __('OpenAI (GPT-4o / GPT-4o-mini)', 'answermesh'), value: 'openai' },
                            { label: __('Google Gemini (1.5 Flash / Pro)', 'answermesh'), value: 'gemini' },
                            { label: __('Offline / Rule-based (No external LLM)', 'answermesh'), value: 'offline' },
                        ]}
                        onChange={setProvider}
                    />

                    {provider !== 'offline' && (
                        <>
                            <TextControl
                                label={__('API Key', 'answermesh')}
                                type="password"
                                value={apiKey}
                                onChange={setApiKey}
                                help={__('Leave unchanged to keep current encrypted key.', 'answermesh')}
                                placeholder="sk-..."
                            />

                            {provider === 'openai' ? (
                                <SelectControl
                                    label={__('OpenAI Model', 'answermesh')}
                                    value={openAiModel}
                                    options={[
                                        { label: 'gpt-4o-mini (Recommended)', value: 'gpt-4o-mini' },
                                        { label: 'gpt-4o', value: 'gpt-4o' },
                                    ]}
                                    onChange={setOpenAiModel}
                                />
                            ) : (
                                <SelectControl
                                    label={__('Gemini Model', 'answermesh')}
                                    value={geminiModel}
                                    options={[
                                        { label: 'gemini-1.5-flash (Fast & Cost-effective)', value: 'gemini-1.5-flash' },
                                        { label: 'gemini-1.5-pro', value: 'gemini-1.5-pro' },
                                    ]}
                                    onChange={setGeminiModel}
                                />
                            )}

                            <div style={{ marginTop: '12px' }}>
                                <Button
                                    variant="secondary"
                                    onClick={handleTestAiConnection}
                                    isBusy={testingAi}
                                    disabled={testingAi}
                                >
                                    {testingAi ? __('Testing Connection...', 'answermesh') : __('Test AI Connection', 'answermesh')}
                                </Button>
                            </div>
                        </>
                    )}
                </div>

                <div className="answermesh-settings-card">
                    <h3 className="answermesh-card-title">{__('Brand Voice & Guardrails', 'answermesh')}</h3>
                    <p className="answermesh-card-desc">
                        {__('Direct the AI tone. Ground truth rules strictly prevent invented claims.', 'answermesh')}
                    </p>

                    <TextareaControl
                        label={__('Brand Tone & Style Directives', 'answermesh')}
                        value={brandVoice}
                        onChange={setBrandVoice}
                        help={__('e.g., Direct, expert, conversational, authoritative. Avoid corporate buzzwords.', 'answermesh')}
                        rows={4}
                    />

                    <h3 className="answermesh-card-title" style={{ marginTop: '20px' }}>{__('Crawler Boundaries', 'answermesh')}</h3>
                    <TextControl
                        label={__('Max Audit Crawl Limit', 'answermesh')}
                        type="number"
                        value={maxCrawlPages}
                        onChange={setMaxCrawlPages}
                        help={__('Default 50 for Free tier. Pro tier allows up to 500 pages.', 'answermesh')}
                    />

                    <ToggleControl
                        label={__('Enable Anonymous Diagnostic Telemetry', 'answermesh')}
                        checked={enableTelemetry}
                        onChange={setEnableTelemetry}
                        help={__('Help improve AnswerMesh heuristics by sharing anonymized error rates.', 'answermesh')}
                    />
                </div>
            </div>
        </div>
    );
}
