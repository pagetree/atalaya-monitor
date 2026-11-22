<section id="view-add" hidden>
  <form id="add-form" class="sheet-card" novalidate>
    <section class="add-block add-identity">
      <div class="add-block-head">
        <h2>What to watch</h2>
        <p>The address we request, and the name on the board.</p>
      </div>
      <div class="identity-grid">
        <label class="identity-address">
          <span class="lab">Address</span>
          <input name="url" type="url" inputmode="url" maxlength="2000" spellcheck="false" placeholder="https://" autocomplete="url" required>
        </label>
        <label class="identity-name">
          <span class="lab">Name</span>
          <input name="name" maxlength="80" autocomplete="off" placeholder="Name on the board">
        </label>
      </div>
    </section>

    <section class="add-block">
      <div class="add-block-head">
        <h2>Type</h2>
        <p>This is the Type column on the board. It also decides what counts as up.</p>
      </div>
      <fieldset class="every" aria-label="Monitor type">
        <div class="kind-grid">
          <label class="every-opt kind-opt">
            <input type="radio" name="kind" value="website" checked>
            <span class="every-main">Website</span>
            <span class="every-sub">A public page. A readable 2xx reply counts as up.</span>
          </label>
          <label class="every-opt kind-opt">
            <input type="radio" name="kind" value="api">
            <span class="every-main">API</span>
            <span class="every-sub">An endpoint. A 2xx that comes back as a web page is only a warning.</span>
          </label>
          <label class="every-opt kind-opt">
            <input type="radio" name="kind" value="keyword">
            <span class="every-main">Keyword</span>
            <span class="every-sub">The reply must contain a phrase you choose.</span>
          </label>
        </div>
      </fieldset>
      <label class="phrase-field" id="phrase-field" hidden>
        <span class="lab">Phrase</span>
        <input name="phrase" maxlength="120" autocomplete="off" placeholder="Words that must appear in the reply">
      </label>
    </section>

    <div class="add-pair">
      <section class="add-block">
        <div class="add-block-head">
          <h2>How often</h2>
          <p>How many times a day we request this address.</p>
        </div>
        <fieldset class="every" aria-label="Check every">
          <div class="every-grid cadence-grid">
            <label class="every-opt"><input type="radio" name="interval" value="60000"><span class="every-main">1 min</span><span class="every-sub">1,440 a day</span></label>
            <label class="every-opt"><input type="radio" name="interval" value="300000" checked><span class="every-main">5 min</span><span class="every-sub">288 a day</span></label>
            <label class="every-opt"><input type="radio" name="interval" value="900000"><span class="every-main">15 min</span><span class="every-sub">96 a day</span></label>
            <label class="every-opt"><input type="radio" name="interval" value="1800000"><span class="every-main">30 min</span><span class="every-sub">48 a day</span></label>
            <label class="every-opt"><input type="radio" name="interval" value="3600000"><span class="every-main">1 hour</span><span class="every-sub">24 a day</span></label>
          </div>
        </fieldset>
      </section>
      <section class="add-block">
        <div class="add-block-head">
          <h2>Slow after</h2>
          <p>A reply slower than this is a warning. No reply is down.</p>
        </div>
        <fieldset class="every" aria-label="Slow after">
          <div class="every-grid slow-grid">
            <label class="every-opt"><input type="radio" name="slow" value="1000"><span class="every-main">1 second</span><span class="every-sub">Tight</span></label>
            <label class="every-opt"><input type="radio" name="slow" value="2000" checked><span class="every-main">2 seconds</span><span class="every-sub">Usual</span></label>
            <label class="every-opt"><input type="radio" name="slow" value="5000"><span class="every-main">5 seconds</span><span class="every-sub">Patient</span></label>
          </div>
        </fieldset>
      </section>
    </div>

    <section class="add-block">
      <div class="add-block-head">
        <h2>Where to check</h2>
        <p>Pick one or more regions. A reply in one place can still fail in another.</p>
      </div>
      <fieldset class="every" aria-label="Regions">
        <div class="every-grid region-grid">
          <label class="every-opt"><input type="checkbox" name="region" value="frankfurt" checked><span class="every-main">Frankfurt</span><span class="every-sub">Europe</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="capetown" checked><span class="every-main">Cape Town</span><span class="every-sub">Africa</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="singapore" checked><span class="every-main">Singapore</span><span class="every-sub">Asia</span></label>
          <label class="every-opt"><input type="checkbox" name="region" value="sydney" checked><span class="every-main">Sydney</span><span class="every-sub">Oceania</span></label>
        </div>
      </fieldset>
    </section>

    <section class="add-block add-notify">
      <div class="add-block-head">
        <h2>How to notify</h2>
        <p>Where we send a note when a check goes down. Email is the only path for now.</p>
      </div>
      <fieldset class="every" aria-label="On an incident">
        <div class="every-grid notify-grid">
          <label class="every-opt"><input type="checkbox" name="notify" value="email" checked><span class="every-main">Email</span><span class="every-sub"><?= htmlspecialchars((string) ($appUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></label>
        </div>
      </fieldset>
    </section>

    <p id="form-error" class="form-error" role="alert" hidden></p>
    <div class="sheet-foot">
      <p>Saving runs the first check right away.</p>
      <div class="sheet-actions">
        <a class="btn-quiet" href="#monitors">Cancel</a>
        <button type="submit" class="btn-ink">Add monitor</button>
      </div>
    </div>
  </form>
</section>
