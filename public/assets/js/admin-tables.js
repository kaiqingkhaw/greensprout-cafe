'use strict';
document.addEventListener('DOMContentLoaded', () => {
    for (const [id,label,card,exportType] of [['users-table','customers','users','customers'],['orders-table','orders','orders','orders'],['reviews-table','reviews','reviews','reviews']]) {
        const table = document.getElementById(id);
        if (!table) continue;
        const toolbar = document.createElement('div'); toolbar.className = 'table-tools';
        const input = document.createElement('input'); input.type = 'search'; input.placeholder = 'Search ' + label + '…'; input.setAttribute('aria-label','Search '+label);
        const info = document.createElement('span'); info.setAttribute('role','status');
        const exportLink = document.createElement('a'); exportLink.href = 'admin_export.php?type=' + exportType; exportLink.className = 'table-export'; exportLink.textContent = 'Export CSV'; exportLink.setAttribute('aria-label','Export '+label+' as CSV');
        toolbar.append(input,info,exportLink); table.parentElement.before(toolbar);
        const pager = document.createElement('nav'); pager.className = 'table-pager'; pager.setAttribute('aria-label',label+' pages');
        const previous = document.createElement('button'); previous.type='button'; previous.textContent='Previous';
        const pageInfo = document.createElement('span'); pageInfo.setAttribute('aria-live','polite');
        const next = document.createElement('button'); next.type='button'; next.textContent='Next';
        pager.append(previous,pageInfo,next); table.parentElement.after(pager);
        let page = 1; const pageSize = 10;
        const sync = () => {
            const rows = [...table.querySelectorAll('tbody tr[data-id]')];
            const query = input.value.trim().toLowerCase();
            const matches = rows.filter(row => row.textContent.toLowerCase().includes(query));
            const pages = Math.max(1,Math.ceil(matches.length/pageSize)); page=Math.min(page,pages);
            rows.forEach(row => { row.hidden = true; });
            matches.slice((page-1)*pageSize,page*pageSize).forEach(row => { row.hidden=false; });
            info.textContent = matches.length ? `${matches.length} of ${rows.length} ${label}` : query ? 'No matches. Try another search.' : `No ${label} yet.`;
            pageInfo.textContent = `Page ${page} of ${pages}`; previous.disabled=page===1; next.disabled=page===pages || !matches.length; pager.hidden=matches.length<=pageSize;
            const count = document.querySelector('.dashboard-card.'+card+' p'); if (count) count.textContent = String(rows.length);
        };
        input.addEventListener('input',()=>{page=1;sync();});
        previous.addEventListener('click',()=>{if(page>1){page--;sync();table.scrollIntoView({behavior:'smooth',block:'start'});}});
        next.addEventListener('click',()=>{page++;sync();table.scrollIntoView({behavior:'smooth',block:'start'});});
        new MutationObserver(sync).observe(table.tBodies[0],{childList:true,subtree:true,characterData:true});
        sync();
    }
});
