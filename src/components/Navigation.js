/**
 * Main Tabbed Navigation Component for AnswerMesh Admin.
 */

import { useState, useEffect } from '@wordpress/element';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import DashboardView from './DashboardView';
import RecommendationsView from './RecommendationsView';
import KnowledgeBaseView from './KnowledgeBaseView';
import PageAnalysisView from './PageAnalysisView';
import ClustersView from './ClustersView';
import LinkDecayView from './LinkDecayView';
import VisibilityView from './VisibilityView';
import SettingsView from './SettingsView';

export default function Navigation() {
    const [currentTab, setCurrentTab] = useState('dashboard');

    useEffect(() => {
        // Read URL query param to select initial tab if landed from submenu
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page');
        if (page === 'answermesh-knowledge') {
            setCurrentTab('knowledge');
        } else if (page === 'answermesh-clusters') {
            setCurrentTab('clusters');
        } else if (page === 'answermesh-links') {
            setCurrentTab('links');
        } else if (page === 'answermesh-visibility') {
            setCurrentTab('visibility');
        } else if (page === 'answermesh-pages') {
            setCurrentTab('pages');
        } else if (page === 'answermesh-settings') {
            setCurrentTab('settings');
        }
    }, []);

    const tabs = [
        {
            name: 'dashboard',
            title: __('Dashboard & Scores', 'answermesh'),
            className: 'answermesh-tab-dashboard',
        },
        {
            name: 'recommendations',
            title: __('Recommendations', 'answermesh'),
            className: 'answermesh-tab-recommendations',
        },
        {
            name: 'knowledge',
            title: __('Business Knowledge Base', 'answermesh'),
            className: 'answermesh-tab-knowledge',
        },
        {
            name: 'clusters',
            title: __('Topic Clusters', 'answermesh'),
            className: 'answermesh-tab-clusters',
        },
        {
            name: 'links',
            title: __('Links & Freshness', 'answermesh'),
            className: 'answermesh-tab-links',
        },
        {
            name: 'visibility',
            title: __('AI Visibility', 'answermesh'),
            className: 'answermesh-tab-visibility',
        },
        {
            name: 'pages',
            title: __('Page Inventory', 'answermesh'),
            className: 'answermesh-tab-pages',
        },
        {
            name: 'settings',
            title: __('Settings & AI', 'answermesh'),
            className: 'answermesh-tab-settings',
        },
    ];

    return (
        <div className="answermesh-app-wrapper">
            <header className="answermesh-app-header">
                <div className="answermesh-brand">
                    <div className="answermesh-logo-icon">▲</div>
                    <div>
                        <h1 className="answermesh-app-title">{__('AnswerMesh', 'answermesh')}</h1>
                        <span className="answermesh-app-subtitle">
                            {__('AI Search Visibility & AEO / GEO Optimization', 'answermesh')}
                        </span>
                    </div>
                </div>
            </header>

            <TabPanel
                className="answermesh-main-tabs"
                activeClass="is-active"
                tabs={tabs}
                initialTabName={currentTab}
                onSelect={(tabName) => setCurrentTab(tabName)}
            >
                {(tab) => {
                    switch (tab.name) {
                        case 'dashboard':
                            return <DashboardView onNavigateTab={setCurrentTab} />;
                        case 'recommendations':
                            return <RecommendationsView />;
                        case 'knowledge':
                            return <KnowledgeBaseView />;
                        case 'clusters':
                            return <ClustersView />;
                        case 'links':
                            return <LinkDecayView />;
                        case 'visibility':
                            return <VisibilityView />;
                        case 'pages':
                            return <PageAnalysisView />;
                        case 'settings':
                            return <SettingsView />;
                        default:
                            return null;
                    }
                }}
            </TabPanel>
        </div>
    );
}
