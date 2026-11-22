<section id="view-monitors">
  <div class="hero">
    <div>
      <h2 class="headline">Your services. Under control.</h2>
      <p class="lede">Simple. Free. Reliable monitoring for developers.</p>
    </div>
    <div id="status-card" class="status-card">
      <p id="summary" class="status-line" role="status">No monitors yet</p>
      <p id="summary-detail" class="status-sub">No checks yet.</p>
    </div>
  </div>

  <div class="section-head">
    <h3 id="monitors-title"><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M2 4h12M2 8h12M2 12h12"/></svg><span id="monitors-count">Monitors (0)</span></h3>
    <a class="add-link" href="#add"><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3v10M3 8h10"/></svg>Add Monitor</a>
  </div>
  <table class="sheet" aria-labelledby="monitors-title">
    <thead>
      <tr>
        <th scope="col">Name</th>
        <th scope="col">Type</th>
        <th scope="col">Target</th>
        <th scope="col">Status</th>
        <th scope="col">Last Check</th>
        <th scope="col">Uptime (30d)</th>
        <th scope="col">Actions</th>
      </tr>
    </thead>
    <tbody id="monitor-rows"></tbody>
  </table>

  <div class="columns">
    <section class="col">
      <h3><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5.5"/><path d="M8 5v3.2l2.2 1.4"/></svg>Recent Events</h3>
      <ul id="recent" class="events"></ul>
      <p class="more-link"><a href="#log"><svg class="ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg>View all events</a></p>
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
          <svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="2.2" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M8 1.6v1.8M8 12.6v1.8M1.6 8h1.8M12.6 8h1.8M3.2 3.2l1.3 1.3M11.5 11.5l1.3 1.3M12.8 3.2l-1.3 1.3M4.5 11.5l-1.3 1.3" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
          <a href="#settings">Account settings</a>
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
