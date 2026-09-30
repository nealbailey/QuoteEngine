// JavaScript for managing the QuoteEngine web application. 
// Handles tab navigation, fetching and displaying quotes, and filtering.
const state = {
  page: 1,
  totalPages: 1,
  sortBy: "date",
  sortOrder: "desc",
  author: "",
  search: ""
};

// Element definitions
const tabs = document.querySelectorAll("[data-tab]");
const panels = document.querySelectorAll("[role='tabpanel']");
const authorFilter = document.querySelector("#author-filter");
const searchInput = document.querySelector("#quote-search");
const quotesBody = document.querySelector("#quotes-body");

// local variables for managing search debounce, fetch controller, and current quote state.
let searchTimer;
let quotesController;
let currentQuote = null;

//<summary>
// Activates the specified tab and updates the URL hash.
// @param {string} panelId The ID of the panel to activate.
//</summary>
function activateTab(panelId) {
  tabs.forEach((tab) => {
    const active = tab.dataset.tab === panelId;
    tab.classList.toggle("is-active", active);
    tab.setAttribute("aria-selected", active ? "true" : "false");
  });
  panels.forEach((panel) => {
    panel.hidden = panel.id !== panelId;
    panel.classList.toggle("is-active", panel.id === panelId);
  });
  history.replaceState(null, "", `#${panelId}`);
}

tabs.forEach((tab) => tab.addEventListener("click", () => activateTab(tab.dataset.tab)));
document.querySelector("[data-tab-link]").addEventListener("click", (event) => {
  event.preventDefault();
  activateTab("random");
});

//<summary>
// Fetches JSON data from the specified URL with the given options.
// @param {string} url The URL to fetch.
// @param {object} options The fetch options.
// @returns {Promise<object>} The parsed JSON data.
//</summary>
async function getJson(url, options) {
  const response = await fetch(url, options);
  let data;
  try {
    data = await response.json();
  } catch {
    throw new Error("The server returned an unexpected response.");
  }
  if (!response.ok) throw new Error(data.message || "The request could not be completed.");
  return data;
}

//<summary>
// Loads a random quote from the server and displays it in the random quote panel.
//</summary>
async function loadRandomQuote() {
  const quoteElement = document.querySelector("#random-quote");
  const authorElement = document.querySelector("#random-author");
  quoteElement.textContent = "Loading a thought...";
  authorElement.textContent = "";
  setCurrentQuote(null);

  try {
    const data = await getJson("getquotes.php?format=json&sortby=random&limit=1");
    quoteElement.textContent = data.quotes[0]?.Value || "The collection is waiting for its first quote.";
    authorElement.textContent = data.quotes[0]?.Author || "";
    setCurrentQuote(data.quotes[0] || null);
  } catch (error) {
    quoteElement.textContent = error.message;
  }
}

//<summary>
// Sets the current quote and updates the edit button state.
// @param {object|null} quote The quote object to set as current, or null to clear.
//</summary>
function setCurrentQuote(quote) {
  currentQuote = quote;
  document.querySelector("#edit-quote").disabled = !quote;
}

//<summary>
// Displays the specified quote in the random quote panel and updates the current quote.
// @param {object} quote The quote object to display.
//</summary>
function showQuote(quote) {
  document.querySelector("#random-quote").textContent = quote.Value;
  document.querySelector("#random-author").textContent = quote.Author;
  setCurrentQuote(quote);
  // Restart auto-refresh so the chosen quote gets a full interval on screen.
  if (autoRefreshTimer) {
    clearInterval(autoRefreshTimer);
    autoRefreshTimer = setInterval(loadRandomQuote, 60000);
  }
  activateTab("random");
}

//<summary>
// Populates the author filter dropdown and datalist with the provided authors.
// @param {string[]} authors The list of authors to populate.
//</summary>
function fillAuthors(authors) {
  if (authorFilter.options.length > 1) return;
  const datalist = document.querySelector("#author-options");
  authors.forEach((author) => {
    const option = document.createElement("option");
    option.value = author;
    option.textContent = author;
    authorFilter.append(option);
    datalist.append(option.cloneNode(true));
  });
}

//<summary>
// Renders the list of quotes in the quotes table.
// @param {object} data The data object containing quotes and pagination info.
//</summary>
function renderQuotes(data) {
  quotesBody.replaceChildren();
  data.quotes.forEach((quote) => {
    const row = document.createElement("tr");
    row.className = "quote-row";
    row.tabIndex = 0;
    row.title = "Show this quote";
    [quote.Value, quote.Author, quote.Added].forEach((value) => {
      const cell = document.createElement("td");
      cell.textContent = value;
      row.append(cell);
    });
    row.addEventListener("click", () => showQuote(quote));
    row.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        showQuote(quote);
      }
    });
    quotesBody.append(row);
  });

  state.totalPages = data.totalPages;
  document.querySelector("#empty-state").hidden = data.quotes.length !== 0;
  document.querySelector("#result-count").textContent = `${data.total} ${data.total === 1 ? "quote" : "quotes"}`;
  document.querySelector("#page-status").textContent = `Page ${data.page} of ${data.totalPages}`;
  document.querySelector("#previous-page").disabled = data.page <= 1;
  document.querySelector("#next-page").disabled = data.page >= data.totalPages;
  fillAuthors(data.authors);
}

//<summary>
// Loads quotes from the server based on the current state and updates the quotes table.
//</summary>
async function loadQuotes() {
  quotesController?.abort();
  quotesController = new AbortController();
  const parameters = new URLSearchParams({
    format: "json",
    page: state.page,
    limit: 10,
    sortby: state.sortBy,
    sortorder: state.sortOrder
  });
  if (state.author) parameters.set("author", state.author);
  if (state.search) parameters.set("search", state.search);

  try {
    const data = await getJson(`getquotes.php?${parameters}`, { signal: quotesController.signal });
    renderQuotes(data);
  } catch (error) {
    if (error.name !== "AbortError") {
      quotesBody.replaceChildren();
      document.querySelector("#empty-state").textContent = error.message;
      document.querySelector("#empty-state").hidden = false;
    }
  }
}

//<summary>
// Handles changes to the author filter and reloads quotes accordingly.
//</summary>
authorFilter.addEventListener("change", () => {
  state.author = authorFilter.value;
  state.page = 1;
  loadQuotes();
});

//<summary>
// Handles changes to the search input and reloads quotes accordingly.
//</summary>
searchInput.addEventListener("input", () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.search = searchInput.value.trim();
    state.page = 1;
    loadQuotes();
  }, 250);
});

//<summary>
// Handles clicks on sort buttons and updates the sort state accordingly.
//</summary>
document.querySelectorAll("[data-sort]").forEach((button) => {
  button.addEventListener("click", () => {
    const nextSort = button.dataset.sort;
    state.sortOrder = state.sortBy === nextSort && state.sortOrder === "asc" ? "desc" : "asc";
    state.sortBy = nextSort;
    state.page = 1;
    loadQuotes();
  });
});

//<summary>
// Handles clicks on the previous page button and loads the previous page of quotes.
//</summary>
document.querySelector("#previous-page").addEventListener("click", () => {
  if (state.page > 1) {
    state.page -= 1;
    loadQuotes();
  }
});
//<summary>
// Handles clicks on the next page button and loads the next page of quotes.
//</summary>
document.querySelector("#next-page").addEventListener("click", () => {
  if (state.page < state.totalPages) {
    state.page += 1;
    loadQuotes();
  }
});

//<summary>
// Sets the edit mode for the quote form based on whether a quote is being edited.
// @param {object|null} quote The quote object to edit, or null to exit edit mode.
//</summary>
function setEditMode(quote) {
  const editing = Boolean(quote);
  document.querySelector("#original-added").value = editing ? quote.Added : "";
  document.querySelector("#original-author").value = editing ? quote.Author : "";
  document.querySelector("#form-eyebrow").textContent = editing ? "Refine the collection" : "Grow the collection";
  document.querySelector("#form-title").textContent = editing ? "Update these words." : "Add words worth remembering.";
  document.querySelector("#form-hint").textContent = editing
    ? `Editing a quote added ${quote.Added}. Saving replaces it and stamps it with the current time.`
    : "Choose someone already in the collection or enter a new author.";
  document.querySelector("#submit-button").textContent = editing ? "Update quote" : "Add to collection";
  document.querySelector("#cancel-edit").hidden = !editing;
}

//<summary>
// Checks if the quote form is currently in edit mode.
// @returns {boolean} True if editing an existing quote, false otherwise.
//</summary>
function isEditing() {
  return document.querySelector("#original-added").value !== "";
}

//<summary>
// Handles clicks on the edit quote button and populates the form with the current quote for editing.
//</summary>
document.querySelector("#edit-quote").addEventListener("click", () => {
  if (!currentQuote) return;
  const message = document.querySelector("#form-message");
  message.textContent = "";
  message.classList.remove("is-error");
  setEditMode(currentQuote);
  document.querySelector("#submit-author").value = currentQuote.Author;
  document.querySelector("#submit-quote").value = currentQuote.Value;
  activateTab("submit");
  document.querySelector("#submit-quote").focus();
});

//<summary>
// Handles clicks on the cancel edit button and resets the quote form.
//</summary>
document.querySelector("#cancel-edit").addEventListener("click", () => {
  document.querySelector("#quote-form").reset();
  document.querySelector("#form-message").textContent = "";
  setEditMode(null);
});

//<summary>
// Handles the submission of the quote form, either adding a new quote or updating an existing one.
//</summary>
document.querySelector("#quote-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const message = document.querySelector("#form-message");
  const submitButton = form.querySelector("button[type='submit']");
  const editing = isEditing();
  message.textContent = editing ? "Updating quote..." : "Adding quote...";
  message.classList.remove("is-error");
  submitButton.disabled = true;
  try {
    const data = await getJson("submit.php", { method: "POST", body: new FormData(form) });
    message.textContent = data.message;
    form.reset();
    setEditMode(null);
    if (editing && data.quote) {
      document.querySelector("#random-quote").textContent = data.quote.Value;
      document.querySelector("#random-author").textContent = data.quote.Author;
      setCurrentQuote(data.quote);
    }
    authorFilter.replaceChildren(new Option("All authors", ""));
    document.querySelector("#author-options").replaceChildren();
    state.page = 1;
    loadQuotes();
  } catch (error) {
    message.textContent = error.message;
    message.classList.add("is-error");
  } finally {
    submitButton.disabled = false;
  }
});

document.querySelector("#another-quote").addEventListener("click", loadRandomQuote);

let autoRefreshTimer = setInterval(loadRandomQuote, 60000);
const autoRefreshButton = document.querySelector("#auto-refresh");

autoRefreshButton.addEventListener("click", () => {
  const enabled = autoRefreshButton.getAttribute("aria-pressed") !== "true";
  autoRefreshButton.setAttribute("aria-pressed", enabled ? "true" : "false");
  clearInterval(autoRefreshTimer);
  autoRefreshTimer = enabled ? setInterval(loadRandomQuote, 60000) : undefined;
});
const initialTab = ["random", "quotes", "submit"].includes(location.hash.slice(1)) ? location.hash.slice(1) : "random";

activateTab(initialTab);
loadRandomQuote();
loadQuotes();