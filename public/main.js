const app = new Vue({
    el: '#app',

    data() {
        return {
            input: '',
            searching: false,
            result: {
                document_ids: [],
                scores: {},
                total_hits: 0,
                stats: {
                    raw: {
                        execution_time: 0,
                        memory_usage: 0,
                    },
                    formatted: {
                        execution_time: '-',
                        memory_usage: '-',
                    },
                },
                dictionaries: [],
            },
            settings: {
                global: {
                    algorithm: 'bm25',
                    search_results: 20,
                    use_inflections: true,
                    low_freq_cutoff: 0.0025
                },
                default: {
                    exact_score: 20,
                    inflection_score: 16,
                    proximity_score: 1,
                    is_lemma_multiplier: 1.3,
                    is_repeated_multiplier: 0.5,
                    result_cutoff_multiplier: 0.4,
                    max_duplicate_scores: 5
                },
                bm25: {
                    max_query_documents: 50000,
                    k1: 1.2,
                    b: 0.75
                }
            },
            defaultSettings: '{"global":{"algorithm":"bm25","search_results":20,"use_inflections":true,"low_freq_cutoff":0.0025},"default":{"exact_score":20,"inflection_score":16,"proximity_score":1,"is_lemma_multiplier":1.3,"is_repeated_multiplier":0.5,"result_cutoff_multiplier":0.4,"max_duplicate_scores":5},"bm25":{"max_query_documents":50000,"k1":1.2,"b":0.75}}'
        };
    },

    created() {
        const settings = window.localStorage.getItem('settings');
        if (settings) {
            this.settings = JSON.parse(settings);
        }

        const searchParams = new URLSearchParams(window.location.search);

        this.input = searchParams.get('q') || '';

        if (this.input !== '') {
            this.search();
        }
    },

    watch: {
        settings: {
            handler(val) {
                window.localStorage.setItem('settings', JSON.stringify(val));
                this.search();
            },
            deep: true
        }
    },

    methods: {
        reset() {
            this.settings = JSON.parse(this.defaultSettings);
        },

        search() {
            if (this.searching === true) {
                return;
            }

            this.searching = true;
            const input = this.input.trim();

            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('q', input);
            const newUrl = window.location.pathname + '?' + searchParams.toString();
            history.pushState(null, '', newUrl);

            if (input === '') {
                return;
            }

            axios.post('api.php', {
                q: input,
                settings: this.settings
            })
            .then((response) => {
                this.searching = false;
                this.result = response.data;
            })
            .then(() => {
                PR.prettyPrint();
            })
            .catch(() => {
                this.searching = false;
            });
        },
    }
})
