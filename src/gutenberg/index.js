/**
 * AnswerMesh Gutenberg Sidebar Registration.
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import AnswerMeshSidebar from './sidebar';

const PLUGIN_NAME = 'answermesh-sidebar';

const AnswerMeshSidebarPlugin = () => (
    <>
        <PluginSidebarMoreMenuItem target={PLUGIN_NAME} icon="networking">
            {__('AnswerMesh Visibility', 'answermesh')}
        </PluginSidebarMoreMenuItem>
        <PluginSidebar
            name={PLUGIN_NAME}
            title={__('AnswerMesh Visibility & AEO', 'answermesh')}
            icon="networking"
        >
            <AnswerMeshSidebar />
        </PluginSidebar>
    </>
);

registerPlugin(PLUGIN_NAME, {
    render: AnswerMeshSidebarPlugin,
    icon: 'networking',
});
