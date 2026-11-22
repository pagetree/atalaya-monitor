<section id="view-main">
  <div class="kpis" aria-label="Sample board figures">
    <article class="kpi">
      <div class="kpi-top">
        <h3>Uptime</h3>
        <span>Sample</span>
      </div>
      <p class="kpi-figure"><span class="kpi-value">99.98</span><span class="kpi-unit">%</span></p>
      <p class="kpi-delta good">0.04 points higher</p>
      <svg class="kpi-chart" viewBox="0 0 200 52" aria-hidden="true">
        <line x1="0" y1="46" x2="200" y2="46" stroke="#ececec" stroke-width="1"/>
        <path d="M0 30 C18 28 28 22 40 24 C58 27 70 18 86 20 C104 22 112 12 128 14 C146 16 154 8 172 10 C186 12 194 7 200 8 V52 H0 Z" fill="#0b5d24" opacity="0.12"/>
        <path d="M0 30 C18 28 28 22 40 24 C58 27 70 18 86 20 C104 22 112 12 128 14 C146 16 154 8 172 10 C186 12 194 7 200 8" fill="none" stroke="#0b5d24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </article>
    <article class="kpi">
      <div class="kpi-top">
        <h3>Reply time</h3>
        <span>Sample</span>
      </div>
      <p class="kpi-figure"><span class="kpi-value">186</span><span class="kpi-unit">ms</span></p>
      <p class="kpi-delta good">22 ms faster</p>
      <svg class="kpi-chart" viewBox="0 0 200 52" aria-hidden="true">
        <line x1="0" y1="46" x2="200" y2="46" stroke="#ececec" stroke-width="1"/>
        <path d="M0 34 C20 32 32 28 48 30 C66 32 74 22 92 20 C110 18 120 24 138 16 C156 8 172 14 200 10" fill="none" stroke="#141414" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="200" cy="10" r="2.5" fill="#0b5d24"/>
      </svg>
    </article>
    <article class="kpi">
      <div class="kpi-top">
        <h3>Incidents</h3>
        <span>Sample</span>
      </div>
      <p class="kpi-figure"><span class="kpi-value">2</span><span class="kpi-unit">open</span></p>
      <p class="kpi-delta watch">1 opened today</p>
      <svg class="kpi-chart kpi-bars" viewBox="0 0 200 52" aria-hidden="true">
        <line x1="0" y1="48" x2="200" y2="48" stroke="#ececec" stroke-width="1"/>
        <rect x="8" y="36" width="16" height="12" rx="2" fill="#e4e4e4"/>
        <rect x="36" y="38" width="16" height="10" rx="2" fill="#e4e4e4"/>
        <rect x="64" y="32" width="16" height="16" rx="2" fill="#e4e4e4"/>
        <rect x="92" y="40" width="16" height="8" rx="2" fill="#e4e4e4"/>
        <rect x="120" y="22" width="16" height="26" rx="2" fill="#c2410c"/>
        <rect x="148" y="34" width="16" height="14" rx="2" fill="#e4e4e4"/>
        <rect x="176" y="14" width="16" height="34" rx="2" fill="#9b1b1b"/>
      </svg>
    </article>
    <article class="kpi">
      <div class="kpi-top">
        <h3>Monitors</h3>
        <span>Sample</span>
      </div>
      <p class="kpi-figure"><span class="kpi-value">18</span><span class="kpi-unit">of 20</span></p>
      <p class="kpi-delta good">18 answering</p>
      <div class="kpi-meter">
        <p>2 quiet right now</p>
        <svg class="kpi-ring" viewBox="0 0 48 48" aria-hidden="true">
          <circle cx="24" cy="24" r="16" fill="none" stroke="#ececec" stroke-width="5"/>
          <circle cx="24" cy="24" r="16" fill="none" stroke="#0b5d24" stroke-width="5" stroke-dasharray="88 100.5" transform="rotate(-90 24 24)"/>
          <circle cx="24" cy="24" r="16" fill="none" stroke="#c2410c" stroke-width="5" stroke-dasharray="8 100.5" stroke-dashoffset="-90" transform="rotate(-90 24 24)"/>
        </svg>
      </div>
    </article>
  </div>
  <div class="columns">
    <section class="col">
      <h3><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5.5"/><path d="M8 5v3.2l2.2 1.4"/></svg>Recent Events</h3>
      <ul id="recent" class="events"></ul>
      <p class="more-link"><a href="#log"><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg>View logs</a></p>
    </section>
    <section class="col">
      <h3><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M9 1.5L4 9h4l-1 5.5L12 7H8l1-5.5z"/></svg>Quick Actions</h3>
      <ul class="qa">
        <li>
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2v12M2 8h12" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
          <a href="#add">Add a new monitor</a>
        </li>
        <li>
          <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="1.5" y="3.5" width="13" height="9" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M2 4l6 5 6-5" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
          <a href="#alerts">Configure alerts</a>
        </li>
        <li>
          <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5.5"/><path d="M2.6 8h10.8M8 2.6c1.6 1.8 2.4 3.6 2.4 5.4S9.6 11.6 8 13.4C6.4 11.6 5.6 9.8 5.6 8S6.4 4.4 8 2.6z" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
          <a href="#status">Open the public status page</a>
        </li>
      </ul>
    </section>
    <aside class="col about">
      <h3><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5.5"/><path d="M8 7.2V11.2M8 5h.01"/></svg>About atalaya</h3>
      <p>A simple and free monitoring service for developers. It shows when your sites, APIs or services go down.</p>
      <hr>
      <p>No ads. No nonsense. 100% free.</p>
      <p class="heart"><span aria-hidden="true">♥</span> Built for developers.</p>
    </aside>
  </div>
</section>
