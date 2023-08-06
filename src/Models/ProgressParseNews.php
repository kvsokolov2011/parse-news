<?php

namespace Cher4geo35\ParseNews\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressParseNews extends Model
{
    private static $queue = [];
    private static $tokens = [
        'summ' => false,
        'summ_incr' => false,
        'err' => false,
        'err_add' => false,
        'compl' => false,
        'compl_incr' => false,
        'res' => false,
        'res_add' => false,
        'clr' => false,
    ];

    private static function serviceRequest($token){
        array_unshift(self::$queue, $token);
        $fault = 1;
        while(!self::$tokens[$token]){
            $fault++;
            sleep(0.1);
            if(end(self::$queue) == $token) self::$tokens['$token'] = true;
            if($fault == 10) break;
        }
    }

    private static function done(){
        array_pop(self::$queue);
    }

    /**
     * @return void
     *
     * Очистка
     */
    public static function clearProgress(){
        self::serviceRequest('clr');
        ProgressParseNews::truncate();
        $query = new ProgressParseNews;
        $query->summary_jobs = 0;
        $query->completed_jobs = 0;
        $query->error = '';
        $query->result = '';
        $query->save();
        self::done();
    }

    /**
     * @return mixed
     *
     * Возвращает общее количество задач
     */
    public static function summaryJobs(){
        self::serviceRequest('summ');
        $query = ProgressParseNews::first();
        self::done();
        return $query->summary_jobs;
    }

    /**
     * @return void
     *
     * Инкремент общего количества задач
     */
    public static function summaryJobsIncrement(){
        self::serviceRequest('summ_incr');
        $query = ProgressParseNews::first();
        $query->summary_jobs = $query->summary_jobs + 40;
        $query->save();
        self::done();
    }

    /**
     * @return mixed
     *
     * Завершенные задачи
     */
    public static function completedJobs(){
        self::serviceRequest('compl');
        $query = ProgressParseNews::first();
        self::done();
        return $query->completed_jobs;
    }

    /**
     * @return void
     *
     * Инкремент завершенных задач
     */
    public static function completedJobsIncrement(){
        self::serviceRequest('summ_incr');
        $query = ProgressParseNews::first();
        $query->completed_jobs++;
        $query->save();
        self::done();
    }

    /**
     * @return mixed
     *
     * Результат импорта новостей
     */
    public static function resultParseNews(){
        self::serviceRequest('res');
        $query = ProgressParseNews::first();
        self::done();
        return $query->result;
    }

    /**
     * @param $result
     * @return mixed
     *
     * Запись результата импорта новостей
     */
    public static function resultParseNewsAdd($result){
        self::serviceRequest('res_add');
        $query = ProgressParseNews::first();
        $query->result = $result;
        $query->save();
        self::done();
        return $query->result;
    }

    /**
     * @return mixed
     *
     * Ошибки импорта
     */
    public static function errorParseNews(){
        self::serviceRequest('err');
        $query = ProgressParseNews::first();
        self::done();
        return $query->error;
    }

    /**
     * @param $error
     * @return mixed
     *
     * Добавление ошибок импорта
     */
    public static function errorParseNewsAdd($error){
        self::serviceRequest('err_add');
        $query = ProgressParseNews::first();
        if($query->error) {
            $query->error = $query->error . "<br>" . $error;
        } else {
            $query->error = $error;
        }
        $query->save();
        self::done();
        return $query->result;
    }
}
