/**
 * Score Gauge Component for AnswerMesh.
 * Displays overall visibility score and 5-layer diagnostic progress meters.
 */

import { __ } from '@wordpress/i18n';

export default function ScoreGauge({ scores, rating, summary }) {
    const overall = Number(scores?.overall ?? 0);
    const layers = [
        { key: 'seo', label: __('Technical SEO', 'answermesh'), weight: '20%', value: scores?.seo ?? 0 },
        { key: 'aeo', label: __('Answer Engine Optimization (AEO)', 'answermesh'), weight: '20%', value: scores?.aeo ?? 0 },
        { key: 'geo', label: __('Generative Engine (GEO)', 'answermesh'), weight: '20%', value: scores?.geo ?? 0 },
        { key: 'entity', label: __('Entity Clarity', 'answermesh'), weight: '15%', value: scores?.entity ?? 0 },
        { key: 'trust', label: __('Trust & Citations', 'answermesh'), weight: '15%', value: scores?.trust ?? 0 },
        { key: 'content', label: __('Content Quality', 'answermesh'), weight: '10%', value: scores?.content ?? 0 },
    ];

    const getScoreColor = (val) => {
        if (val >= 80) return '#10b981'; // green
        if (val >= 60) return '#3b82f6'; // blue
        if (val >= 40) return '#f59e0b'; // amber
        return '#ef4444'; // red
    };

    const getRatingBadgeClass = (score) => {
        if (score >= 80) return 'answermesh-badge-excellent';
        if (score >= 60) return 'answermesh-badge-good';
        if (score >= 40) return 'answermesh-badge-fair';
        return 'answermesh-badge-needs-attention';
    };

    return (
        <div className="answermesh-score-gauge-card">
            <div className="answermesh-gauge-header">
                <div>
                    <h2 className="answermesh-gauge-title">
                        {__('AnswerMesh Visibility Score', 'answermesh')}
                    </h2>
                    <p className="answermesh-gauge-subtitle">
                        {summary || __('Composite diagnostic score across search and generative AI engines.', 'answermesh')}
                    </p>
                </div>
                <div className={`answermesh-rating-badge ${getRatingBadgeClass(overall)}`}>
                    {rating || (overall >= 70 ? __('Healthy', 'answermesh') : __('Needs Attention', 'answermesh'))}
                </div>
            </div>

            <div className="answermesh-gauge-main">
                <div className="answermesh-radial-display" style={{ borderColor: getScoreColor(overall) }}>
                    <span className="answermesh-radial-number">{Math.round(overall)}</span>
                    <span className="answermesh-radial-max">/100</span>
                </div>

                <div className="answermesh-layer-breakdown">
                    {layers.map((layer) => {
                        const val = Math.min(100, Math.max(0, Number(layer.value)));
                        return (
                            <div key={layer.key} className="answermesh-layer-row">
                                <div className="answermesh-layer-info">
                                    <span className="answermesh-layer-name">{layer.label}</span>
                                    <span className="answermesh-layer-weight">({layer.weight})</span>
                                </div>
                                <div className="answermesh-layer-meter-wrapper">
                                    <div className="answermesh-layer-meter-bar">
                                        <div
                                            className="answermesh-layer-meter-fill"
                                            style={{
                                                width: `${val}%`,
                                                backgroundColor: getScoreColor(val),
                                            }}
                                        />
                                    </div>
                                    <span className="answermesh-layer-val">{Math.round(val)}%</span>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
