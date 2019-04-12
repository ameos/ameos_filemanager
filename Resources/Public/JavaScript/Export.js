define(['jquery', 'twbs/bootstrap-datetimepicker'], function ($) {
    'use strict';

    $(function () {
        $('#start').datetimepicker({format:'DD.MM.YYYY'});
        $('#end').datetimepicker({format:'DD.MM.YYYY'});
    });
});