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
                            <select v-bind:value="settings.global.algorithm" @change="settings.global.algorithm = $event.target.value">
                                <option value="default">Default</option>
                                <option value="bm25">BM25</option>
                            </select>
                        </label>
                        <label>
                            Search results:
                            <input v-model="settings.global.search_results" type="number" min="1" step="1">
                        </label>
                        <label>
                            Use inflections:
                            <input v-model="settings.global.use_inflections" type="checkbox">
                        </label>
                        <label>
                            Low freq terms cutoff:
                            <input v-model="settings.global.low_freq_cutoff" type="number" min="0.0001" max="1" step="0.1">
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>Default algorithm:</legend>
                        <label>
                            Exact score:
                            <input v-model="settings.default.exact_score" type="number" min="0" step="0.1">
                        </label>
                        <label>
                            Inflection score:
                            <input v-model="settings.default.inflection_score" type="number" min="0" step="0.1">
                        </label>
                        <label>
                            Proximity score:
                            <input v-model="settings.default.proximity_score" type="number" min="0" step="0.1">
                        </label>
                        <label>
                            Lemma multiplier:
                            <input v-model="settings.default.is_lemma_multiplier" type="number" min="0" step="0.1">
                        </label>
                        <label>
                            Repeated lemma multiplier:
                            <input v-model="settings.default.is_repeated_multiplier" type="number" min="0" step="0.1">
                        </label>
                        <label>
                            Result cutoff multiplier:
                            <input v-model="settings.default.result_cutoff_multiplier" type="number" min="0" max="100" step="0.1">
                        </label>
                        <label>
                            Max duplicate scores:
                            <input v-model="settings.default.max_duplicate_scores" type="number" min="0" max="100" step="1">
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>BM25 (+TF)</legend>
                        <label>
                            Max query documents (b):
                            <input v-model="settings.bm25.max_query_documents" type="number" min="1" step="1">
                        </label>
                        <label>
                            BM25 Boost (b):
                            <input v-model="settings.bm25.b" type="number" min="0" max="100" step="0.01">
                        </label>
                        <label>
                            BM25 k<sub>1</sub>:
                            <input v-model="settings.bm25.k1" type="number" min="0" max="100" step="0.01">
                        </label>
                    </fieldset>
                </div>
                <div class="grid__item search-field">
                    <form @submit.prevent="search">
                        <input :disabled="searching" v-model="input" type="search" placeholder="E.g. Cutting corners" class="search-field__input">
                        <button :disabled="searching" type="submit" class="search-field__button">
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
                            <small>Direction: {{ item.direction }}</small>
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
