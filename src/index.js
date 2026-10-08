/**
 * AnswerMesh Admin App Mount.
 */

import { render, useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import './index.css';

const AnswerMeshApp = () => {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        apiFetch({ path: '/aiseo/v1/status' })
            .then((res) => {
                setStatus(res.data);
                setLoading(false);
            })
            .catch((err) => {
                setError(err.message || __('Failed to load status', 'answermesh'));
                setLoading(false);
            });
    }, []);

    if (loading) {
        return (
            <div className="answermesh-card">
                <p>{__('Loading AnswerMesh diagnostics...', 'answermesh')}</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="answermesh-card answermesh-error">
                <h3>{__('Connection Notice', 'answermesh')}</h3>
                <p>{error}</p>
            </div>
        );
    }

    return (
        <div className="answermesh-container">
            <header className="answermesh-header">
                <div>
                    <h1>{__('AnswerMesh', 'answermesh')}</h1>
                    <p className="answermesh-tagline">
                        {__('AI Search Visibility, AEO & Technical SEO', 'answermesh')}
                    </p>
                </div>
                <div className="answermesh-badge">
                    v{status?.version || '1.0.0'}
                </div>
            </header>

            <div className="answermesh-grid">
                <div className="answermesh-card">
                    <h3>{__('System Status', 'answermesh')}</h3>
                    <ul className="answermesh-status-list">
                        <li>
                            <span>{__('Database Version:', 'answermesh')}</span>
                            <strong>{status?.db_version}</strong>
                        </li>
                        <li>
                            <span>{__('AI Provider:', 'answermesh')}</span>
                            <span className="answermesh-pill">{status?.ai_provider}</span>
                        </li>
                        <li>
                            <span>{__('API Key Connected:', 'answermesh')}</span>
                            <strong>{status?.has_api_key ? __('Yes', 'answermesh') : __('No', 'answermesh')}</strong>
                        </li>
                        <li>
                            <span>{__('PHP Version:', 'answermesh')}</span>
                            <strong>{status?.php_version}</strong>
                        </li>
                    </ul>
                </div>

                <div className="answermesh-card">
                    <h3>{__('Analytics Tables', 'answermesh')}</h3>
                    <ul className="answermesh-table-list">
                        {status?.tables && Object.entries(status.tables).map(([table, exists]) => (
                            <li key={table}>
                                <code>{table}</code>
                                <span className={exists ? 'status-ok' : 'status-missing'}>
                                    {exists ? __('Ready', 'answermesh') : __('Missing', 'answermesh')}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </div>
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('answermesh-admin-root');
    if (rootElement) {
        render(<AnswerMeshApp />, rootElement);
    }
});
