<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Collected Wisdom</title>
  <meta name="description" content="A personal collection of memorable quotations.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Libre+Caslon+Display&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="#random" data-tab-link="random">
      <span class="brand-mark">Q</span>
      <span>Collected Wisdom</span>
    </a>
    <nav class="tabs" role="tablist" aria-label="Quote collection">
      <button class="tab is-active" id="tab-random" role="tab" aria-selected="true" aria-controls="random" data-tab="random">Random</button>
      <button class="tab" id="tab-quotes" role="tab" aria-selected="false" aria-controls="quotes" data-tab="quotes">All quotes</button>
      <button class="tab" id="tab-submit" role="tab" aria-selected="false" aria-controls="submit" data-tab="submit">Add a quote</button>
    </nav>
  </header>

  <main>
    <section class="panel random-panel is-active" id="random" role="tabpanel" aria-labelledby="tab-random">
      <div class="portrait-wrap" aria-hidden="true">
        <img src="https://upload.wikimedia.org/wikipedia/commons/d/d3/Albert_Einstein_Head.jpg" alt="">
      </div>
      <div class="random-content">
        <p class="eyebrow">A thought worth keeping</p>
        <blockquote id="random-quote" aria-live="polite">Loading a thought...</blockquote>
        <p class="quote-author" id="random-author"></p>
        <button class="icon-button" id="another-quote" type="button" title="Show another quote" aria-label="Show another quote">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v6h-6"/></svg>
        </button>
      </div>
    </section>

    <section class="panel quotes-panel" id="quotes" role="tabpanel" aria-labelledby="tab-quotes" hidden>
      <div class="section-heading">
        <div>
          <p class="eyebrow">The collection</p>
          <h1>Words to return to.</h1>
        </div>
        <p id="result-count" class="result-count" aria-live="polite"></p>
      </div>

      <div class="filters">
        <label>
          <span>Author</span>
          <select id="author-filter">
            <option value="">All authors</option>
          </select>
        </label>
        <label class="search-field">
          <span>Search quote text</span>
          <span class="input-with-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input id="quote-search" type="search" placeholder="Search the collection..." autocomplete="off">
          </span>
        </label>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Quote</th>
              <th><button class="sort-button" type="button" data-sort="author">Author <span aria-hidden="true">↕</span></button></th>
              <th><button class="sort-button" type="button" data-sort="date">Added <span aria-hidden="true">↕</span></button></th>
            </tr>
          </thead>
          <tbody id="quotes-body"></tbody>
        </table>
        <p class="empty-state" id="empty-state" hidden>No quotes match those filters.</p>
      </div>

      <div class="pagination" aria-label="Quote pages">
        <button class="page-button" id="previous-page" type="button" aria-label="Previous page">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <span id="page-status">Page 1 of 1</span>
        <button class="page-button" id="next-page" type="button" aria-label="Next page">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      </div>
    </section>

    <section class="panel submit-panel" id="submit" role="tabpanel" aria-labelledby="tab-submit" hidden>
      <div class="form-intro">
        <p class="eyebrow">Grow the collection</p>
        <h1>Add words worth remembering.</h1>
        <p>Choose someone already in the collection or enter a new author.</p>
      </div>
      <form id="quote-form">
        <label>
          <span>Author</span>
          <input id="submit-author" name="author" type="text" list="author-options" autocomplete="off" required maxlength="120">
          <datalist id="author-options"></datalist>
        </label>
        <label>
          <span>Quote</span>
          <textarea id="submit-quote" name="quote" rows="7" required maxlength="2000"></textarea>
        </label>
        <div class="form-actions">
          <p id="form-message" role="status" aria-live="polite"></p>
          <button class="primary-button" type="submit">Add to collection</button>
        </div>
      </form>
    </section>
  </main>

  <script src="app.js"></script>
</body>
</html>