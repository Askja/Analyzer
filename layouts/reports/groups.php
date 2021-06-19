<?php
?>

<section id="groups" class="invisible">
    <div class="groups-list">
        <?php for($k = 0; $k < 200; $k++): ?>
            <div class="group-row">
                <div class="group-icon group"></div>
                <div class="group-name">Test group</div>
                <div class="group-students-count">29 peo.</div>
                <div class="group-controls">
                    <div class="group-control-icon edit-group"></div>
                    <div class="group-control-icon remove-group"></div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <div class="add-group">
        <input type="text" name="group-name" id="group-name" placeholder="name"/>
        <div class="button">
            <span class="material-icons">group_add</span>
            <div class="button-text">Add group</div>
        </div>
    </div>
</section>
