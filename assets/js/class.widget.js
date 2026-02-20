/*
 * Firmware Version Pie Chart - Zabbix 7.4 widget JS class.
 *
 * Data is embedded as JSON in data-pie attribute on the root .fw-pie-widget div.
 * setContents() reads it after the base class sets _body.innerHTML, then renders
 * the canvas pie chart.
 */
class WidgetFirmwarePieChart extends CWidget {

    onInitialize() {
        super.onInitialize();
        this._pie_data  = null;
        this._resize_ob = null;
    }

    onActivate() {
        super.onActivate();
        if (typeof ResizeObserver !== 'undefined') {
            this._resize_ob = new ResizeObserver(() => {
                if (this._pie_data && this._pie_data.error === null) {
                    this._render();
                }
            });
            this._resize_ob.observe(this._body);
        }
    }

    onDeactivate() {
        super.onDeactivate();
        if (this._resize_ob) {
            this._resize_ob.disconnect();
            this._resize_ob = null;
        }
    }

    setContents(response) {
        super.setContents(response);

        const root = this._body.querySelector('.fw-pie-widget');
        if (!root) return;

        try {
            this._pie_data = JSON.parse(root.dataset.pie);
        } catch(e) {
            return;
        }

        this._render();
    }

    _render() {
        if (!this._pie_data) return;

        const { firmware_counts, show_legend, error } = this._pie_data;
        if (error !== null) return;

        const canvas = this._body.querySelector('.fw-pie-canvas');
        if (!canvas) return;

        const entries = Object.entries(firmware_counts);
        if (entries.length === 0) {
            this._renderEmpty(canvas);
            return;
        }

        const wrap   = this._body.querySelector('.fw-pie-wrap');
        const legend = this._body.querySelector('.fw-pie-legend');
        const dpr    = window.devicePixelRatio || 1;
        const cw     = wrap.clientWidth  || 320;
        const ch     = wrap.clientHeight || 260;

        canvas.width        = Math.floor(cw * dpr);
        canvas.height       = Math.floor(ch * dpr);
        canvas.style.width  = cw + 'px';
        canvas.style.height = ch + 'px';

        const ctx    = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        const total  = entries.reduce((s, [, c]) => s + c, 0);
        const colors = this._palette(entries.length);
        const cx     = cw / 2;
        const cy     = ch / 2;
        const r      = Math.min(cx, cy) * 0.86;

        ctx.clearRect(0, 0, cw, ch);

        let angle = -Math.PI / 2;

        entries.forEach(([version, count], i) => {
            const sweep = (count / total) * 2 * Math.PI;

            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, angle, angle + sweep);
            ctx.closePath();
            ctx.fillStyle = colors[i];
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth   = 2;
            ctx.stroke();

            if (sweep > 0.22) {
                const pct = Math.round((count / total) * 100);
                const mid = angle + sweep / 2;
                const lx  = cx + r * 0.60 * Math.cos(mid);
                const ly  = cy + r * 0.60 * Math.sin(mid);
                ctx.fillStyle    = '#ffffff';
                ctx.font         = `bold ${Math.max(11, Math.min(14, r / 6))}px Arial,sans-serif`;
                ctx.textAlign    = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(pct + '%', lx, ly);
            }

            angle += sweep;
        });

        ctx.beginPath();
        ctx.arc(cx, cy, r, 0, 2 * Math.PI);
        ctx.strokeStyle = 'rgba(0,0,0,0.12)';
        ctx.lineWidth   = 1;
        ctx.stroke();

        if (show_legend && legend) {
            legend.innerHTML = '';
            const ul = document.createElement('ul');
            ul.className = 'fw-pie-legend-list';
            entries.forEach(([version, count], i) => {
                const pct = Math.round((count / total) * 100);
                const li  = document.createElement('li');
                li.innerHTML =
                    `<span class="fw-swatch" style="background:${colors[i]}"></span>` +
                    `<span class="fw-ver">${this._esc(version)}</span>` +
                    `<span class="fw-cnt">${count}\u00a0device${count !== 1 ? 's' : ''}\u00a0(${pct}%)</span>`;
                ul.appendChild(li);
            });
            legend.appendChild(ul);
        }
    }

    _renderEmpty(canvas) {
        const dpr = window.devicePixelRatio || 1;
        const w   = canvas.parentElement?.clientWidth  || 200;
        const h   = canvas.parentElement?.clientHeight || 180;
        canvas.width        = w * dpr;
        canvas.height       = h * dpr;
        canvas.style.width  = w + 'px';
        canvas.style.height = h + 'px';
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, w, h);
        ctx.fillStyle    = '#9e9e9e';
        ctx.font         = '13px Arial,sans-serif';
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('No firmware data available', w / 2, h / 2);
    }

    _palette(n) {
        const base = [
            '#4E79A7', '#F28E2B', '#E15759', '#76B7B2',
            '#59A14F', '#EDC948', '#B07AA1', '#FF9DA7',
            '#9C755F', '#BAB0AC', '#1F77B4', '#D62728',
            '#2CA02C', '#FF7F0E', '#9467BD', '#8C564B',
        ];
        return Array.from({length: n}, (_, i) => base[i % base.length]);
    }

    _esc(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
}
