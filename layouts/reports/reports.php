<?php

use engine\Groups;

?>
<section id="reports" class="visible">
    <div class="control-row">
        <div class="control-block">
            <div class="row text-center">
                <label for="selected-group" class="label-filter">Группа:</label>
                <select id="selected-group" class="filter-select">
                    <option value="-1">Все</option>
                    <?php foreach (Groups::getNames() as $gid => $group): ?>
                    <option value="<?= $gid ?>"><?= $group ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row text-center">
                <label for="selected-course" class="label-filter">Курс:</label>
                <select id="selected-course" class="filter-select">
                    <option value="-1">Все</option>
                    <?php for ($k = 1; $k < 5; $k++): ?>
                        <option value="<?= $k ?>"><?= $k ?> курс</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="row text-center">
                <label for="selected-past" class="label-filter">Семестр:</label>
                <select id="selected-past" class="filter-select">
                    <option value="-1">Все</option>
                    <?php for ($k = 1; $k < 3; $k++): ?>
                        <option value="<?= $k ?>"><?= $k ?> полугодие</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="control-block">
            <div class="row">
                <label for="filter-name">Поиск</label>
                <input type="text" id="filter-name" class="filter-text" placeholder="Имя ведомости"/>
            </div>

            <div class="row">
                <div class="button-without-icon">Применить фильтр</div>
            </div>
        </div>
    </div>

    <div class="counters-line">
        <div class="counter">Квалификационные: {{ counters.qualifiers }}</div>
        <div class="counter">Семестровые: {{ counters.half }}</div>
        <div class="counter">Кураторские: {{ counters.admins }}</div>
        <div class="counter">Сводные: {{ counters.summaries }}</div>
        <div class="counter">Итоговые: {{ counters.all }}</div>
    </div>

    <div class="reports-list">
        <div class="report-row sticky-top bg-report-header-row">
            <div class="row-icon"></div>
            <div class="report-title text-bold text-black">Имя</div>
            <div class="report-group text-bold text-black">Группа</div>
            <div class="report-past text-bold text-black">Семестр</div>
        </div>

        <div v-for="(vObj, vId) in vitae" class="report-row">
            <div class="row-icon xls-icon"></div>
            <div class="report-title">{{ vObj.name }}</div>
            <div class="report-group">{{ vObj.group }}</div>
            <div class="report-past">{{ vObj.past }}</div>
            <div class="report-controls">
                <div class="report-icon remove" :key="vId" :data-vid="vId" v-on:click="remove"></div>
                <div class="report-icon download" :key="vId" :data-vid="vId" v-on:click="download"></div>
            </div>
        </div>
    </div>
</section>
