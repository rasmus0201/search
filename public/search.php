<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="utf-8">
        <title>Search</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/styles/vs.min.css">
        <link rel="stylesheet" href="main.css">
    </head>
    <body>
        <div id="app">
            <div class="grid">
                <div class="grid__item settings">
                    <h3 class="settings__title">Settings</h3>
                    <fieldset>
                        <legend>Globals:</legend>
                        <label>
                            Search algorithm:
                            <select>
                                <option value="default" selected>Default</option>
                                <option value="bm25">BM25</option>
                            </select>
                        </label>
                        <label>
                            Search results:
                            <input type="number" min="1" value="20" step="1">
                        </label>
                        <label>
                            Use inflections:
                            <input type="checkbox" checked>
                        </label>
                        <label>
                            Low freq terms cutoff:
                            <input type="number" min="0.0001" max="1" value="0.0025" step="0.1">
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>Default algorithm:</legend>
                        <label>
                            Exact score:
                            <input type="number" min="0" value="20" step="0.1">
                        </label>
                        <label>
                            Inflection score:
                            <input type="number" min="0" value="16" step="0.1">
                        </label>
                        <label>
                            Proximity score:
                            <input type="number" min="0" value="1" step="0.1">
                        </label>
                        <label>
                            Lemma multiplier:
                            <input type="number" min="0" value="1.3" step="0.1">
                        </label>
                        <label>
                            Repeated lemma multiplier:
                            <input type="number" min="0" value="0.5" step="0.1">
                        </label>
                        <label>
                            Result cutoff multiplier:
                            <input type="number" min="0" max="100" value="0.4" step="0.1">
                        </label>
                        <label>
                            Max duplicate scores:
                            <input type="number" min="0" max="100" value="5" step="1">
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>BM25 (+TF)</legend>
                        <label>
                            Max query documents (b):
                            <input type="number" min="1" value="50000" step="1">
                        </label>
                        <label>
                            BM25 Boost (b):
                            <input type="number" min="0" max="100" value="0.75" step="0.01">
                        </label>
                        <label>
                            BM25 K1 (k<sub>1<sub>):
                            <input type="number" min="0" max="100" value="1.2" step="0.01">
                        </label>
                    </fieldset>
                </div>
                <div class="grid__item search-field">
                    <form @submit.prevent="search">
                        <input v-model="input" type="search" placeholder="E.g. Cutting corners" class="search-field__input">
                        <button type="submit" class="search-field__button">
                            Search...
                        </button>
                    </form>
                </div>
                <div class="grid__item search-results">
                    <div class="message">
                        <p>
                            <span>Search hits: {{ result.total_hits }}</span>
                            <br>
                            <span>Search took: {{ result.stats.formatted.execution_time }}</span>
                            <br>
                            <span>Memory usage: {{ result.stats.formatted.memory_usage }}</span>
                        </p>
                    </div>
                    <div class="search-results__items" v-for="(dict, dIndex) in result.dictionaries" :key="'dict-'+dIndex">
                        <h2>{{ dict.dict_name }}</h2>

                        <div class="search-results__item" v-for="(item, index) in dict.entries" :key="'item-'+index">
                            <h3>{{ item.headword }}</h3>
                            <small>EntryId: {{ item.id }}</small>
                            <small>Score: {{ item.score }}</small>
                            <pre class="prettyprint lang-xml" v-text="item.data"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/gh/google/code-prettify@master/loader/run_prettify.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.2/axios.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
        <script src="main.js"></script>
    </body>
</html>
