<?php

?>
<aside>
    <div class="user-info">
        <img class="avatar" src="web/css/default/avatar.jpg" alt="">

        <div class="user-data">
            <div class="name">Егор Плотников</div>
            <div class="user-controls">
                <span class="material-icons md-26 edit">edit</span>
                <span class="material-icons md-26 settings">manage_accounts</span>
                <span class="material-icons md-26 logout">logout</span>
            </div>
        </div>
    </div>

    <div class="menu">
        <div class="menu-item" id="create-reports" data-target="reports" askja-title="Создаются обычные ведомости для выбранной группы">
            <span class="material-icons">add</span>
            <div class="menu-title">Создать ведомости</div>
        </div>
        <div class="menu-item" id="delete-reports" data-target="reports" askja-title="Удаляются все ведомости для выбранной группы">
            <span class="material-icons">delete</span>
            <div class="menu-title">Удалить ведомости</div>
        </div>
        <div class="menu-item" id="update-reports" data-target="reports" askja-title="Обновляются ведомости для выбранной группы, без потери оценок">
            <span class="material-icons">update</span>
            <div class="menu-title">Обновить ведомости</div>
        </div>
        <!--<div class="menu-item" data-target="groups">
            <span class="material-icons">file_copy</span>
            <div class="menu-title">Редактирование списка групп</div>
        </div>
        <div class="menu-item" data-target="students">
            <span class="material-icons">group</span>
            <div class="menu-title">Редактирование студентов</div>
        </div>-->
    </div>

    <div class="status-bar">
        <div class="operation"></div>

        <div class="progress-bar">
            <div class="progress-text"></div>
            <div class="progress-line"></div>
        </div>
    </div>
</aside>
