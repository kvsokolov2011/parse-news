<template>
    <div>
        <form>
            <h2 class="mt-3 mb-3 h4">URL, URI страницы новостей 4geo</h2>
            <div class="d-flex flex-column flex-md-row">
                <div class="form-group mr-md-3">
                    <label>Ссылка на сайт: </label>
                    <input type="text" v-model="link_site" class="form-control" />
                </div>
                <div class="form-group mr-md-3">
                    <label>Ссылка на страницу новости: </label>
                    <input type="text" v-model="uri_news" class="form-control" />
                </div>
                <div class="form-group">
                    <label>Paginator: </label>
                    <input type="text" v-model="uri_paginator" class="form-control" />
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row">
                <div class="form-group mr-md-3">
                    <label>Номер первой страницы: </label>
                    <input type="text" v-model="first_page_number" class="form-control" />
                </div>
                <div class="form-group mr-md-3">
                    <label>Номер последней страницы: </label>
                    <input type="text" v-model="last_page_number" class="form-control" />
                </div>
            </div>

            <div class="form-group">
                <label class="mr-3">Источник главного изображения: </label>
                <select v-model="source_image" class="p-2 mt-3">
                    <option value='page'>Страница Новости</option>
                    <option value='list'>Список новостей</option>
                </select>
            </div>


            <!--Прогресс бар-->
            <div class="my-4 progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" v-bind:style="{'width': persentWidth  }"></div>
            </div>

            <button class="btn btn-success"
                    type="button"
                    :disabled="parsing"
                    v-on:click="parse()">Импорт</button>

            <div class="my-4">
                <h4 v-if="lastJobs">Количество оставшихся задач импорта: <span>{{ lastJobs }}</span></h4>
                <h4 v-else>Результат импорта: <span style="color: green">{{ parseNewsResult }}</span></h4>
            </div>

            <!--Настройки-->
            <div v-on:click="showSettings()" class="progress-bar__btn"><u>{{ inscription }}</u></div>
            <div class="pl-5" :style="{'opacity': opacitySettings, 'height': heightSettings, 'transition': 'opacity 1s ease' }">
                <h2 class="mt-5 mb-3 h4">Парсинг страницы со списком новостей 4geo</h2>

                <div class="form-group mr-md-3">
                    <label>Путь к title новости: </label>
                    <input type="text" v-model="path_title" class="form-control" />
                </div>

                <div class="form-group mr-md-3">
                    <label>Путь к ссылке на страницу новости: </label>
                    <input type="text" v-model="path_link" class="form-control" />
                </div>

                <div class="form-group mr-md-3">
                    <label>Путь к Short новости: </label>
                    <input type="text" v-model="path_short" class="form-control" />
                </div>

                <div v-if="listImage" class="form-group mr-md-3">
                    <label>Путь к картинке новости: </label>
                    <input type="text" v-model="path_image_list" class="form-control" />
                </div>

                <h2 class="mt-5 mb-3 h4">Парсинг страницы новости 4geo</h2>
                <div class="form-group mr-md-3">
                    <label>Путь к полному описанию новости: </label>
                    <input type="text" v-model="path_description" class="form-control" />
                </div>
                <div class="form-group mr-md-3">
                    <label>Путь к дате создания новости: </label>
                    <input type="text" v-model="path_date" class="form-control" />
                </div>
                <div v-if="pageImage" class="form-group mr-md-3">
                    <label>Путь к картинке новости: </label>
                    <input type="text" v-model="path_image" class="form-control" />
                </div>
                <div class="form-group mr-md-3">
                    <label>Путь к картинкам галереи: </label>
                    <input type="text" v-model="path_gallery" class="form-control" />
                </div>

                <h2 class="mt-5 mb-3 h4">Парсинг meta 4geo</h2>
                <div class="form-group mr-md-3">
                    <label>Мета title: </label>
                    <input type="text" v-model="path_meta_title" class="form-control" />
                </div>
                <div class="form-group mr-md-3">
                    <label>Мета description: </label>
                    <input type="text" v-model="path_meta_description" class="form-control" />
                </div>
                <div class="form-group mr-md-3">
                    <label>Мета keywords: </label>
                    <input type="text" v-model="path_meta_keywords" class="form-control" />
                </div>
            </div>
        </form>
        <div v-if="parseNewsError" class="my-4">
            <h5>Ошибки импорта:</h5>
            <div class="progress-bar__errors" v-html="parseNewsError"></div>
        </div>
    </div>

</template>
<script>
    export default {
        props: ['url', 'import'],
        data: function() {
            return {
                width: 0,
                persentWidth: '0%',
                timer: '',
                parsing: false,
                parseNewsResult: '',
                parseNewsError: '',
                lastJobs: 0,
                link_site: 'https://xn--80aabffge6atsmb6aa.xn--p1ai',
                uri_news: '/news',
                uri_paginator: '/page',
                first_page_number: '1',
                last_page_number: '1',
                source_image: 'page',
                settings: false,
                opacitySettings: '0',
                heightSettings: '0',
                inscription: 'Показать настройки >>>',
                path_title: '//div[@class="col-md-12 news-item marginbottom20"]//div//h4//strong//a',
                path_link: '//div[@class="col-md-12 news-item marginbottom20"]//div//h4//strong//a/@href',
                path_short: '//div[@class="col-md-12 news-item marginbottom20"]//div//div//div[@class="post-decription marginbottom15"]',
                path_image_list: '//div[@class="col-xs-12 col-sm-12 col-md-3"]//a[@class="thumbnail news-img"]//img/@src',
                path_description: '//div[@class="default-style"]//div[@class="lp-element lp-text1"]',
                path_date: '//div[@class="post visit-news"]//div[@class="post_date"]',
                path_image: '//div[@class="lp-element lp-text1"]//img/@src',
                path_gallery: '//div[@class="lp-element lp-photoslider3"]//div//a[@class="previewphoto4s lp-slideshow-tile-image"]/@href',
                path_meta_title: '//title',
                path_meta_description: '//meta[@name="description"]/@content',
                path_meta_keywords: 'path_meta_keywords',
            }
        },

        methods: {
            clear(){
                this.parseNewsResult = '';
                this.parseNewsError = '';
            },
            parse(){
                this.parsing = true;
                this.clear();
                let formData = new FormData();
                formData.append("link_site", this.link_site);
                formData.append("uri_news", this.uri_news);
                formData.append("uri_paginator", this.uri_paginator);
                formData.append("first_page_number", this.first_page_number);
                formData.append("last_page_number", this.last_page_number);
                formData.append("source_image", this.source_image);
                formData.append("path_title", this.path_title);
                formData.append("path_link", this.path_link);
                formData.append("path_short", this.path_short);
                formData.append("path_image", this.path_image);
                formData.append("path_image_list", this.path_image_list);
                formData.append("path_description", this.path_description);
                formData.append("path_date", this.path_date);
                formData.append("path_gallery", this.path_gallery);
                formData.append("path_meta_title", this.path_meta_title);
                formData.append("path_meta_description", this.path_meta_description);
                formData.append("path_meta_keywords", this.path_meta_keywords);

                axios
                    .post(this.import, formData, {
                        responseType: 'json'
                    })
                    .then(response => {
                        let result = response.data;
                        if(!result.success){
                            this.parseNewsResult = result.result;
                        } else {
                            this.parseNewsResult = "Идет импорт новостей...";
                            this.timer = setInterval(() => { this.getProgress(); }, 1000);
                        }
                    })
                    .catch(error => {
                        this.parseNewsResult = 'Неожиданный ответ с сервера';
                    })
            },
            showSettings(){
                if(this.settings){
                    this.heightSettings = "0";
                    this.opacitySettings = "0";
                    this.inscription = 'Показать настройки >>>';
                    this.settings = false;
                } else {
                    this.heightSettings = "auto";
                    this.opacitySettings = "1";
                    this.inscription = 'Скрыть настройки <<<';
                    this.settings = true;
                }
            },

            getProgress() {
                axios.get(this.url).then((response) => {
                    this.width = response.data.width;
                    this.lastJobs = response.data.lastJobs;
                    if(response.data.width === 0 || response.data.width === 100) this.stop++;
                    if(this.width >= 100 || response.data.error === 'Ошибка обработчика очередей. Импорт прекращен.') {
                        clearInterval(this.timer);
                        this.parsing = false;
                    }
                    this.persentWidth = this.width + '%';
                    if( response.data.result)  this.parseNewsResult = response.data.result;
                    this.parseNewsError = response.data.error;
                });


            },
        },
        computed: {
            pageImage() {
                if(this.source_image === 'page'){
                    return true;
                }
            },
            listImage() {
                if(this.source_image === 'list'){
                    return true;
                }
            }
        },
        mounted() {
            this.clear();
        }
    }
</script>

<style scoped>
    .progress-bar__errors{
        width: 100%;
        padding: 0.5rem 1rem;
    }
    .progress-bar__btn{
        cursor: pointer;
        font-size: 1.5rem;
    }
</style>
