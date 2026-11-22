<section id="view-add" hidden>
  <form id="add-form" class="sheet-card" novalidate>
    <div class="sheet-block add-form">
      <h2>New monitor</h2>
      <p class="sheet-sub">A name, the address to check, and how often.</p>
      <div class="add-pair">
        <label>
          <span class="lab">Name</span>
          <input name="name" maxlength="80" autocomplete="off">
        </label>
        <label>
          <span class="lab">Address</span>
          <input name="url" type="url" inputmode="url" maxlength="2000" spellcheck="false" placeholder="https://" autocomplete="url" required>
        </label>
      </div>
      <fieldset class="every">
        <legend class="lab">Check every</legend>
        <div class="every-grid">
          <label class="every-opt"><input type="radio" name="interval" value="60000"><span>1 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="300000" checked><span>5 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="900000"><span>15 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="1800000"><span>30 min</span></label>
          <label class="every-opt"><input type="radio" name="interval" value="3600000"><span>1 hour</span></label>
        </div>
      </fieldset>
      <p class="hint">Warning means a reply over 2 seconds, or a status code that could not be read.</p>
      <p id="form-error" class="form-error" role="alert" hidden></p>
    </div>
    <div class="sheet-foot">
      <p>It starts checking as soon as you save it.</p>
      <div class="sheet-actions">
        <a class="btn-quiet" href="#monitors">Cancel</a>
        <button type="submit" class="btn-ink">Add monitor</button>
      </div>
    </div>
  </form>
</section>
