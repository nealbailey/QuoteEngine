const state = {
  page: 1,
  totalPages: 1,
  sortBy: "date",
  sortOrder: "desc",
  author: "",
  search: ""
};

const tabs = document.querySelectorAll("[data-tab]");
const panels = document.querySelectorAll("[role='tabpanel']");
const authorFilter = document.querySelector("#author-filter");
const searchInput = document.querySelector("#quote-search");
const quotesBody = document.querySelector("#quotes-body");
let searchTimer;
let quotesController;

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

async function getJson(url, options) {
  const response = await fetch(url, options);
  const data = await response.json();
  if (!response.ok) throw new Error(data.message || "The request could not be completed.");
  return data;
}

async function loadRandomQuote() {
  const quoteElement = document.querySelector("#random-quote");
  const authorElement = document.querySelector("#random-author");
  quoteElement.textContent = "Loading a thought...";
  authorElement.textContent = "";
  try {
    const data = await getJson("getquotes.php?format=json&sortby=random&limit=1");
    quoteElement.textContent = data.quotes[0]?.Value || "The collection is waiting for its first quote.";
    authorElement.textContent = data.quotes[0]?.Author || "";
  } catch (error) {
    quoteElement.textContent = error.message;
  }
}

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

function renderQuotes(data) {
  quotesBody.replaceChildren();
  data.quotes.forEach((quote) => {
    const row = document.createElement("tr");
    [quote.Value, quote.Author, quote.Added].forEach((value) => {
      const cell = document.createElement("td");
      cell.textContent = value;
      row.append(cell);
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

authorFilter.addEventListener("change", () => {
  state.author = authorFilter.value;
  state.page = 1;
  loadQuotes();
});

searchInput.addEventListener("input", () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.search = searchInput.value.trim();
    state.page = 1;
    loadQuotes();
  }, 250);
});

document.querySelectorAll("[data-sort]").forEach((button) => {
  button.addEventListener("click", () => {
    const nextSort = button.dataset.sort;
    state.sortOrder = state.sortBy === nextSort && state.sortOrder === "asc" ? "desc" : "asc";
    state.sortBy = nextSort;
    state.page = 1;
    loadQuotes();
  });
});

document.querySelector("#previous-page").addEventListener("click", () => {
  if (state.page > 1) {
    state.page -= 1;
    loadQuotes();
  }
});
document.querySelector("#next-page").addEventListener("click", () => {
  if (state.page < state.totalPages) {
    state.page += 1;
    loadQuotes();
  }
});

document.querySelector("#quote-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const message = document.querySelector("#form-message");
  const submitButton = form.querySelector("button[type='submit']");
  message.textContent = "Adding quote...";
  message.classList.remove("is-error");
  submitButton.disabled = true;
  try {
    const data = await getJson("submit.php", { method: "POST", body: new FormData(form) });
    message.textContent = data.message;
    form.reset();
    authorFilter.replaceChildren(new Option("All authors", ""));
    document.querySelector("#author-options").replaceChildren();
    loadQuotes();
  } catch (error) {
    message.textContent = error.message;
    message.classList.add("is-error");
  } finally {
    submitButton.disabled = false;
  }
});

document.querySelector("#another-quote").addEventListener("click", loadRandomQuote);
const initialTab = ["random", "quotes", "submit"].includes(location.hash.slice(1)) ? location.hash.slice(1) : "random";
activateTab(initialTab);
loadRandomQuote();
loadQuotes();