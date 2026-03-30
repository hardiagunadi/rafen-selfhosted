<style>
    .cm-page {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .cm-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.9rem;
        padding: 1rem 1.1rem;
        border: 1px solid var(--app-border);
        border-radius: 18px;
        background:
            radial-gradient(circle at 0 0, rgba(14, 165, 233, 0.12), transparent 28%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 249, 255, 0.96));
        box-shadow: var(--app-shadow-soft);
    }

    .cm-header-main {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        min-width: 0;
    }

    .cm-header-icon {
        width: 3rem;
        height: 3rem;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #fff;
        font-size: 1rem;
        box-shadow: 0 10px 24px rgba(2, 132, 199, 0.24);
    }

    .cm-header-copy {
        min-width: 0;
    }

    .cm-kicker {
        margin: 0 0 0.2rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--app-text-soft);
    }

    .cm-title {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.15;
        color: var(--app-text);
    }

    .cm-subtitle {
        margin: 0.28rem 0 0;
        font-size: 0.86rem;
        color: var(--app-text-soft);
    }

    .cm-header-actions {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    .cm-btn,
    .cm-btn:focus {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        min-height: 2.5rem;
        padding: 0 1rem;
        border-radius: 11px;
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform 140ms ease, box-shadow 140ms ease, opacity 140ms ease;
    }

    .cm-btn:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .cm-btn-primary {
        color: #fff;
        background: linear-gradient(135deg, #0f6fa2, #1387bb);
        box-shadow: 0 10px 24px rgba(19, 103, 164, 0.22);
        border: 1px solid rgba(15, 111, 162, 0.34);
    }

    .cm-btn-primary:hover {
        color: #fff;
    }

    .cm-btn-muted {
        color: var(--app-text);
        background: #fff;
        border: 1px solid var(--app-border);
    }

    .cm-btn-danger {
        color: #fff;
        background: linear-gradient(135deg, #dc2626, #ef4444);
        border: 1px solid rgba(220, 38, 38, 0.3);
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
    }

    .cm-btn-danger:hover {
        color: #fff;
    }

    .cm-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.9fr);
        gap: 1rem;
        align-items: start;
    }

    .cm-main-card,
    .cm-side-card {
        background: var(--app-surface);
        border: 1px solid var(--app-border);
        border-radius: 18px;
        box-shadow: var(--app-shadow-soft);
        overflow: hidden;
    }

    .cm-main-card-header,
    .cm-side-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        background: linear-gradient(180deg, #fbfdff 0%, #f5f9ff 100%);
        border-bottom: 1px solid #e4ebf5;
    }

    .cm-main-card-title,
    .cm-side-card-title {
        margin: 0;
        font-size: 0.96rem;
        font-weight: 800;
        color: var(--app-text);
    }

    .cm-main-card-subtitle,
    .cm-side-card-subtitle {
        margin: 0.15rem 0 0;
        font-size: 0.78rem;
        color: var(--app-text-soft);
    }

    .cm-main-card-body,
    .cm-side-card-body {
        padding: 1rem;
    }

    .cm-side-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .cm-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .cm-metric {
        padding: 0.8rem 0.9rem;
        border: 1px solid #e3ebf4;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff, #f9fbff);
    }

    .cm-metric-label {
        margin: 0 0 0.25rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--app-text-soft);
    }

    .cm-metric-value {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: var(--app-text);
        word-break: break-word;
    }

    .cm-summary-list {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }

    .cm-summary-item {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.7rem;
        border-bottom: 1px dashed #dbe5f0;
    }

    .cm-summary-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .cm-summary-label {
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--app-text-soft);
    }

    .cm-summary-value {
        text-align: right;
        font-size: 0.84rem;
        font-weight: 700;
        color: var(--app-text);
        word-break: break-word;
    }

    .cm-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.62rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.02em;
    }

    .cm-badge-success {
        color: #166534;
        background: #dcfce7;
    }

    .cm-badge-danger {
        color: #991b1b;
        background: #fee2e2;
    }

    .cm-badge-warning {
        color: #92400e;
        background: #fef3c7;
    }

    .cm-badge-neutral {
        color: #334155;
        background: #e2e8f0;
    }

    .cm-alert {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        padding: 0.9rem 1rem;
        border-radius: 14px;
        border: 1px solid transparent;
        font-size: 0.84rem;
    }

    .cm-alert-danger {
        color: #991b1b;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .cm-form-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e4ebf5;
    }

    .cm-form-meta {
        font-size: 0.78rem;
        color: var(--app-text-soft);
    }

    .cm-form-actions {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        flex-wrap: wrap;
    }

    .cm-action-list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .cm-action-list .cm-btn {
        width: 100%;
        justify-content: flex-start;
    }

    @media (max-width: 1199.98px) {
        .cm-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .cm-header {
            padding: 0.9rem;
        }

        .cm-main-card-body,
        .cm-side-card-body {
            padding: 0.9rem;
        }

        .cm-metrics {
            grid-template-columns: 1fr;
        }

        .cm-form-toolbar {
            align-items: stretch;
        }

        .cm-form-actions {
            width: 100%;
        }

        .cm-form-actions .cm-btn,
        .cm-header-actions .cm-btn {
            width: 100%;
        }
    }
</style>
