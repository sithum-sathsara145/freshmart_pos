/* FreshMart — lightweight self-contained SVG charts for the reports suite.
 * No dependencies. Dark-theme palette matching the app. Render targets are
 * <div data-chart> elements whose config lives in a <script type="application/json">
 * child (so nothing has to be escaped into HTML attributes).
 *
 * Chart config shape:
 *   line:  { type:'line',  labels:[...], series:[{name,data,color?,dashed?}, ...], money?:bool }
 *   bar:   { type:'bar',   labels:[...], data:[...], colors?:[...], money?:bool, horizontal?:bool }
 *   donut: { type:'donut', labels:[...], data:[...], colors?:[...], money?:bool }
 */
(function () {
    const PALETTE = ['#818cf8', '#4ade80', '#f87171', '#fbbf24', '#60a5fa', '#c084fc', '#2dd4bf', '#fb923c'];
    const AXIS = '#3a3f52', GRID = '#232735', TEXT = '#64748b', TEXT2 = '#94a3b8';
    const NS = 'http://www.w3.org/2000/svg';

    const el = (n, a) => { const e = document.createElementNS(NS, n); for (const k in (a || {})) e.setAttribute(k, a[k]); return e; };
    const fmt = (v, money) => {
        const n = Number(v) || 0;
        const s = Math.abs(n) >= 1000 ? n.toLocaleString('en-US', { maximumFractionDigits: 0 })
                                      : n.toLocaleString('en-US', { maximumFractionDigits: 2 });
        return money ? 'Rs. ' + s : s;
    };
    const niceMax = (m) => {
        if (m <= 0) return 10;
        const pow = Math.pow(10, Math.floor(Math.log10(m)));
        const n = m / pow;
        const step = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10;
        return step * pow;
    };

    // ---- tooltip (one shared node) --------------------------------------
    let tip;
    function tooltip() {
        if (!tip) {
            tip = document.createElement('div');
            tip.style.cssText = 'position:fixed;z-index:9999;pointer-events:none;opacity:0;transition:opacity .1s;'
                + 'background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;padding:6px 9px;'
                + 'font-size:11px;color:#e2e8f0;box-shadow:0 6px 18px rgba(0,0,0,.5);white-space:nowrap';
            document.body.appendChild(tip);
        }
        return tip;
    }
    const showTip = (html, x, y) => { const t = tooltip(); t.innerHTML = html; t.style.opacity = '1'; t.style.left = (x + 12) + 'px'; t.style.top = (y + 12) + 'px'; };
    const hideTip = () => { if (tip) tip.style.opacity = '0'; };

    // ---- line / area ----------------------------------------------------
    function lineChart(host, cfg) {
        const W = 720, H = 240, pl = 54, pr = 14, pt = 14, pb = 28;
        const iw = W - pl - pr, ih = H - pt - pb;
        const labels = cfg.labels || [];
        const series = cfg.series || [];
        const n = labels.length || 1;
        let max = 0;
        series.forEach(s => (s.data || []).forEach(v => { if (v > max) max = v; }));
        max = niceMax(max);
        const x = i => pl + (n <= 1 ? iw / 2 : (iw * i) / (n - 1));
        const y = v => pt + ih - (ih * (Number(v) || 0)) / max;

        const svg = el('svg', { viewBox: `0 0 ${W} ${H}`, width: '100%', preserveAspectRatio: 'xMidYMid meet', style: 'display:block' });

        // gridlines + y labels
        for (let g = 0; g <= 4; g++) {
            const gy = pt + (ih * g) / 4;
            svg.appendChild(el('line', { x1: pl, y1: gy, x2: W - pr, y2: gy, stroke: GRID, 'stroke-width': 1 }));
            const t = el('text', { x: pl - 8, y: gy + 3, fill: TEXT, 'font-size': 9, 'text-anchor': 'end' });
            t.textContent = fmt(max * (1 - g / 4), cfg.money);
            svg.appendChild(t);
        }
        // x labels (thinned)
        const step = Math.ceil(n / 8);
        labels.forEach((lb, i) => {
            if (i % step && i !== n - 1) return;
            const t = el('text', { x: x(i), y: H - 9, fill: TEXT, 'font-size': 9, 'text-anchor': 'middle' });
            t.textContent = lb;
            svg.appendChild(t);
        });

        series.forEach((s, si) => {
            const color = s.color || PALETTE[si % PALETTE.length];
            const data = s.data || [];
            const pts = data.map((v, i) => [x(i), y(v)]);
            const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
            if (!s.dashed) {
                const areaId = 'g' + Math.random().toString(36).slice(2, 8);
                const defs = el('defs');
                const lg = el('linearGradient', { id: areaId, x1: 0, y1: 0, x2: 0, y2: 1 });
                lg.appendChild(el('stop', { offset: '0%', 'stop-color': color, 'stop-opacity': .28 }));
                lg.appendChild(el('stop', { offset: '100%', 'stop-color': color, 'stop-opacity': 0 }));
                defs.appendChild(lg); svg.appendChild(defs);
                const area = line + ` L ${x(n - 1)} ${pt + ih} L ${x(0)} ${pt + ih} Z`;
                svg.appendChild(el('path', { d: area, fill: `url(#${areaId})` }));
            }
            svg.appendChild(el('path', { d: line, fill: 'none', stroke: color, 'stroke-width': 2,
                'stroke-dasharray': s.dashed ? '5 4' : '', 'stroke-opacity': s.dashed ? .6 : 1,
                'stroke-linejoin': 'round', 'stroke-linecap': 'round' }));
        });

        // hover overlay
        const focus = el('line', { x1: 0, y1: pt, x2: 0, y2: pt + ih, stroke: AXIS, 'stroke-width': 1, opacity: 0 });
        svg.appendChild(focus);
        const dots = series.map((s, si) => { const c = el('circle', { r: 3.5, fill: s.color || PALETTE[si % PALETTE.length], stroke: '#0f1117', 'stroke-width': 1.5, opacity: 0 }); svg.appendChild(c); return c; });
        const hit = el('rect', { x: pl, y: pt, width: iw, height: ih, fill: 'transparent' });
        svg.appendChild(hit);
        hit.addEventListener('mousemove', ev => {
            const r = svg.getBoundingClientRect();
            const relX = (ev.clientX - r.left) / r.width * W;
            let i = Math.round(((relX - pl) / iw) * (n - 1));
            i = Math.max(0, Math.min(n - 1, i));
            focus.setAttribute('x1', x(i)); focus.setAttribute('x2', x(i)); focus.setAttribute('opacity', 1);
            let html = `<div style="color:${TEXT2};margin-bottom:3px">${labels[i] || ''}</div>`;
            series.forEach((s, si) => {
                const v = (s.data || [])[i] || 0;
                dots[si].setAttribute('cx', x(i)); dots[si].setAttribute('cy', y(v)); dots[si].setAttribute('opacity', 1);
                const c = s.color || PALETTE[si % PALETTE.length];
                html += `<div><span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:${c};margin-right:5px"></span>${s.name || ''} <b>${fmt(v, cfg.money)}</b></div>`;
            });
            showTip(html, ev.clientX, ev.clientY);
        });
        hit.addEventListener('mouseleave', () => { hideTip(); focus.setAttribute('opacity', 0); dots.forEach(d => d.setAttribute('opacity', 0)); });

        host.appendChild(svg);
        legend(host, series.map((s, si) => ({ name: s.name, color: s.color || PALETTE[si % PALETTE.length], dashed: s.dashed })));
    }

    // ---- bar (vertical or horizontal) -----------------------------------
    function barChart(host, cfg) {
        const labels = cfg.labels || [], data = cfg.data || [];
        const colors = cfg.colors || labels.map((_, i) => PALETTE[i % PALETTE.length]);
        const n = labels.length || 1;
        let max = niceMax(Math.max(0, ...data.map(Number)));

        if (cfg.horizontal) {
            const rowH = 26, W = 720, pl = 130, pr = 60;
            const H = Math.max(40, n * rowH + 10);
            const iw = W - pl - pr;
            const svg = el('svg', { viewBox: `0 0 ${W} ${H}`, width: '100%', preserveAspectRatio: 'xMidYMid meet', style: 'display:block' });
            labels.forEach((lb, i) => {
                const cy = 8 + i * rowH, bw = iw * (Number(data[i]) || 0) / max;
                const lt = el('text', { x: pl - 8, y: cy + rowH / 2, fill: TEXT2, 'font-size': 11, 'text-anchor': 'end' });
                lt.textContent = lb.length > 20 ? lb.slice(0, 19) + '…' : lb;
                svg.appendChild(lt);
                const bar = el('rect', { x: pl, y: cy + 3, width: Math.max(1, bw), height: rowH - 12, rx: 3, fill: colors[i % colors.length] });
                bar.style.cursor = 'default';
                bar.addEventListener('mousemove', ev => showTip(`${lb}: <b>${fmt(data[i], cfg.money)}</b>`, ev.clientX, ev.clientY));
                bar.addEventListener('mouseleave', hideTip);
                svg.appendChild(bar);
                const vt = el('text', { x: pl + bw + 6, y: cy + rowH / 2, fill: TEXT, 'font-size': 10 });
                vt.textContent = fmt(data[i], cfg.money);
                svg.appendChild(vt);
            });
            host.appendChild(svg);
            return;
        }

        const W = 720, H = 230, pl = 50, pr = 12, pt = 12, pb = 30;
        const iw = W - pl - pr, ih = H - pt - pb;
        const bw = Math.min(46, (iw / n) * 0.62);
        const svg = el('svg', { viewBox: `0 0 ${W} ${H}`, width: '100%', preserveAspectRatio: 'xMidYMid meet', style: 'display:block' });
        for (let g = 0; g <= 4; g++) {
            const gy = pt + (ih * g) / 4;
            svg.appendChild(el('line', { x1: pl, y1: gy, x2: W - pr, y2: gy, stroke: GRID, 'stroke-width': 1 }));
            const t = el('text', { x: pl - 8, y: gy + 3, fill: TEXT, 'font-size': 9, 'text-anchor': 'end' });
            t.textContent = fmt(max * (1 - g / 4), cfg.money); svg.appendChild(t);
        }
        const step = Math.ceil(n / 12);
        labels.forEach((lb, i) => {
            const cx = pl + (iw * (i + 0.5)) / n, v = Number(data[i]) || 0;
            const bh = ih * v / max;
            const bar = el('rect', { x: cx - bw / 2, y: pt + ih - bh, width: bw, height: Math.max(0, bh), rx: 3, fill: colors[i % colors.length] });
            bar.addEventListener('mousemove', ev => showTip(`${lb}: <b>${fmt(v, cfg.money)}</b>`, ev.clientX, ev.clientY));
            bar.addEventListener('mouseleave', hideTip);
            svg.appendChild(bar);
            if (!(i % step)) { const t = el('text', { x: cx, y: H - 10, fill: TEXT, 'font-size': 9, 'text-anchor': 'middle' }); t.textContent = lb; svg.appendChild(t); }
        });
        host.appendChild(svg);
    }

    // ---- donut ----------------------------------------------------------
    function donutChart(host, cfg) {
        const data = (cfg.data || []).map(Number), labels = cfg.labels || [];
        const colors = cfg.colors || labels.map((_, i) => PALETTE[i % PALETTE.length]);
        const total = data.reduce((a, b) => a + b, 0);
        const S = 200, cx = 100, cy = 100, r = 74, sw = 26;
        const wrap = el('svg', { viewBox: `0 0 ${S} ${S}`, width: 180, height: 180, style: 'display:block;margin:0 auto' });
        if (total <= 0) {
            wrap.appendChild(el('circle', { cx, cy, r, fill: 'none', stroke: GRID, 'stroke-width': sw }));
        } else {
            let acc = 0;
            data.forEach((v, i) => {
                if (v <= 0) return;
                const frac = v / total, a0 = acc * 2 * Math.PI - Math.PI / 2;
                acc += frac; const a1 = acc * 2 * Math.PI - Math.PI / 2;
                const large = frac > 0.5 ? 1 : 0;
                const x0 = cx + r * Math.cos(a0), y0 = cy + r * Math.sin(a0);
                const x1 = cx + r * Math.cos(a1), y1 = cy + r * Math.sin(a1);
                const seg = el('path', { d: `M ${x0} ${y0} A ${r} ${r} 0 ${large} 1 ${x1} ${y1}`, fill: 'none', stroke: colors[i % colors.length], 'stroke-width': sw });
                seg.style.cursor = 'default';
                seg.addEventListener('mousemove', ev => showTip(`${labels[i]}: <b>${fmt(v, cfg.money)}</b> (${(frac * 100).toFixed(1)}%)`, ev.clientX, ev.clientY));
                seg.addEventListener('mouseleave', hideTip);
                wrap.appendChild(seg);
            });
        }
        const ct = el('text', { x: cx, y: cy - 2, fill: '#e2e8f0', 'font-size': 15, 'text-anchor': 'middle', 'font-weight': 600 });
        ct.textContent = total >= 1000 ? (total / 1000).toFixed(1) + 'k' : fmt(total, false);
        wrap.appendChild(ct);
        const cl = el('text', { x: cx, y: cy + 14, fill: TEXT, 'font-size': 9, 'text-anchor': 'middle' });
        cl.textContent = 'total'; wrap.appendChild(cl);

        const box = document.createElement('div');
        box.style.cssText = 'display:flex;align-items:center;gap:14px;flex-wrap:wrap';
        box.appendChild(wrap);
        const leg = document.createElement('div');
        leg.style.cssText = 'flex:1;min-width:120px';
        labels.forEach((lb, i) => {
            const pct = total > 0 ? (data[i] / total * 100) : 0;
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:6px;font-size:11px;color:#94a3b8;padding:2px 0';
            row.innerHTML = `<span style="width:9px;height:9px;border-radius:2px;background:${colors[i % colors.length]};flex-shrink:0"></span>`
                + `<span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${lb}</span>`
                + `<span style="color:#e2e8f0;font-weight:500">${fmt(data[i], cfg.money)}</span>`
                + `<span style="color:#64748b;width:38px;text-align:right">${pct.toFixed(0)}%</span>`;
            leg.appendChild(row);
        });
        box.appendChild(leg);
        host.appendChild(box);
    }

    function legend(host, items) {
        if (items.length < 2) return;
        const box = document.createElement('div');
        box.style.cssText = 'display:flex;gap:14px;justify-content:center;margin-top:6px;flex-wrap:wrap';
        items.forEach(it => {
            const s = document.createElement('span');
            s.style.cssText = 'display:flex;align-items:center;gap:5px;font-size:11px;color:#94a3b8';
            s.innerHTML = `<span style="width:14px;height:3px;border-radius:2px;background:${it.color};${it.dashed ? 'opacity:.6' : ''}"></span>${it.name}`;
            box.appendChild(s);
        });
        host.appendChild(box);
    }

    function render(host) {
        const cfgEl = host.querySelector('script[type="application/json"]');
        if (!cfgEl) return;
        let cfg; try { cfg = JSON.parse(cfgEl.textContent); } catch (e) { return; }
        host.querySelectorAll('svg,div.rc-body').forEach(n => n.remove());
        const body = document.createElement('div'); body.className = 'rc-body'; host.appendChild(body);
        if (cfg.type === 'bar') barChart(body, cfg);
        else if (cfg.type === 'donut') donutChart(body, cfg);
        else lineChart(body, cfg);
    }

    function renderAll(root) {
        (root || document).querySelectorAll('[data-chart]').forEach(render);
    }

    window.ReportCharts = { renderAll, render };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => renderAll());
    else renderAll();
})();
