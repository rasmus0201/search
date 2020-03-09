const app = new Vue({
    el: '#app',

    data() {
        return {
            input: '',
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
            settings: {},
        };
    },

    created() {
        const searchParams = new URLSearchParams(window.location.search);

        this.input = searchParams.get('q') || '';

        if (this.input !== '') {
            this.search();
        }
    },

    methods: {
        search() {
            const input = this.input.trim();

            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('q', input);
            const newUrl = window.location.pathname + '?' + searchParams.toString();
            history.pushState(null, '', newUrl);

            if (input === '') {
                return;
            }

            axios.post('api.php', {
                q: input
            })
            .then((response) => {
                this.result = response.data;
            })
            .then(() => {
                PR.prettyPrint();
            });
        },
    }
})
