<template>
    <div>
        <div class="my-4 progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" v-bind:style="{'width': persentWidth  }"></div>
        </div>
        <div class="my-4">
            {{ parseNewsResult }}
            {{ parseNewsError }}
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
                    if(this.width >= 100 || this.stop > 3 || response.data.error === 'Ошибка обработчика очередей.') {
                        clearInterval(this.timer);
                    }
                    this.persentWidth = this.width + '%';
                    this.parseNewsError = response.data.error;
                    this.parseNewsResult = response.data.result;
                    console.log(response.data.error);
                    console.log(this.persentWidth);
                });
            },
        },

        mounted() {
            this.timer = setInterval(() => this.getProgress(), 1000);
        }
    }
</script>
