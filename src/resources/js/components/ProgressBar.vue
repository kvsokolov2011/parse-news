<template>
    <div>
        <div class="my-4 progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" v-bind:style="{'width': persentWidth  }"></div>
        </div>
        <div class="my-4">
            <h4>Результат импорта: <span style="color: green">{{ parseNewsResult }}</span></h4><br>
            <h5>Ошибки импорта:</h5>
            <div class="progress-bar__errors" v-html="parseNewsError"></div>
        </div>
    </div>
</template>
<script>
    export default {
        props: ['url'],
        data: function() {
            return {
                width: 0,
                persentWidth: '0%',
                timer: '',
                stop: 0,
                parseNewsResult: '',
                parseNewsError: '',
            }
        },

        methods: {
            getProgress() {
                axios.get(this.url).then((response) => {
                    if(this.width < response.data.width) this.width = response.data.width;
                    if(response.data.width === 0) this.stop++;
                    if(this.width >= 100 || response.data.error === 'Ошибка обработчика очередей.') {
                        clearInterval(this.timer);
                        document.getElementById('btn-parse').classList.remove('d-none');
                        document.querySelector(".alert-primary").classList.add('d-none');
                    }
                    if(this.stop > 3) {
                        clearInterval(this.timer);
                    }
                    this.persentWidth = this.width + '%';
                    this.parseNewsResult = response.data.result;
                    this.parseNewsError = response.data.error;
                });
            },
        },

        mounted() {
            this.timer = setInterval(() => this.getProgress(), 1000);
        }
    }
</script>

<style scoped>
    .progress-bar__errors{
        width: 100%;
        height: 6rem;
        overflow-y: scroll;
        background-color: #eee;
        border: 0.0625rem solid #aaa;
        padding: 0.5rem 1rem;
    }
</style>
