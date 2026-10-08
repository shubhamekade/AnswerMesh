/**
 * AnswerMesh Admin App Mount.
 */

import { render } from '@wordpress/element';
import Navigation from './components/Navigation';
import './index.css';

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('answermesh-admin-root');
    if (rootElement) {
        render(<Navigation />, rootElement);
    }
});
