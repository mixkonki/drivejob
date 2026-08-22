<style>
    .app-page { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
    .app-page h1 { margin-bottom: .25rem; }
    .app-page .app-lead { color: #6b7280; margin-bottom: 1.5rem; }

    .app-table { width: 100%; border-collapse: collapse; background: #fff;
                 border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
    .app-table th, .app-table td { padding: .75rem 1rem; text-align: left;
                                   border-bottom: 1px solid #f1f2f4; vertical-align: top; }
    .app-table th { background: #f9fafb; font-size: .8rem; text-transform: uppercase;
                    letter-spacing: .03em; color: #6b7280; white-space: nowrap; }
    .app-table tr:last-child td { border-bottom: 0; }
    .app-table td .muted { color: #6b7280; font-size: .85rem; }

    .app-status { display: inline-block; padding: .2rem .6rem; border-radius: 999px;
                  font-size: .8rem; font-weight: 600; white-space: nowrap; }

    .app-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .app-btn { display: inline-block; padding: .4rem .8rem; border-radius: 6px;
               font-size: .85rem; text-decoration: none; border: 1px solid transparent;
               cursor: pointer; font-family: inherit; }
    .app-btn-view { background: #eef2ff; color: #3730a3; border-color: #c7d2fe; }
    .app-btn-ok { background: #dcfce7; color: #166534; border-color: #86efac; }
    .app-btn-no { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .app-btn-quiet { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }

    .app-empty { padding: 3rem 1rem; text-align: center; color: #6b7280;
                 background: #fff; border: 1px dashed #d1d5db; border-radius: 8px; }
    .app-empty a { color: #b3261e; font-weight: 600; }

    .app-pagination { display: flex; gap: 1rem; align-items: center;
                      justify-content: center; margin-top: 1.5rem; color: #6b7280; }
    .app-pagination a { color: #b3261e; text-decoration: none; font-weight: 600; }

    .app-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .app-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.25rem; }
    .app-card h2 { font-size: 1.05rem; margin: 0 0 .75rem; padding-bottom: .5rem;
                   border-bottom: 1px solid #f1f2f4; }
    .app-card dl { margin: 0; display: grid; grid-template-columns: auto 1fr; gap: .4rem 1rem; }
    .app-card dt { color: #6b7280; font-size: .85rem; }
    .app-card dd { margin: 0; }

    .app-message { background: #f9fafb; border-left: 3px solid #d1d5db;
                   padding: 1rem; border-radius: 0 6px 6px 0; white-space: pre-wrap; }

    .app-alert { padding: .85rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
    .app-alert-ok { background: #dcfce7; color: #166534; }
    .app-alert-err { background: #fee2e2; color: #991b1b; }

    @media (max-width: 800px) {
        .app-cards { grid-template-columns: 1fr; }
        .app-table thead { display: none; }
        .app-table, .app-table tbody, .app-table tr, .app-table td { display: block; width: 100%; }
        .app-table tr { border-bottom: 1px solid #e5e7eb; padding: .5rem 0; }
        .app-table td { border: 0; padding: .35rem 1rem; }
        .app-table td::before { content: attr(data-label); display: block;
                                font-size: .75rem; text-transform: uppercase; color: #6b7280; }
    }
</style>
