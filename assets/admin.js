/* Hostnasi Security — admin JS */
jQuery(function ($) {

    $(document).on('click', '.hns-fix-btn', function () {
        var $btn  = $(this);
        var fix   = $btn.data('fix');
        var stepId = $btn.data('step');
        var $msg  = $('#msg-' + stepId);
        var $step = $btn.closest('.hns-step');

        $btn.prop('disabled', true).text('Applying…');
        $msg.removeClass('ok err').hide();

        $.post(HNS.ajax, {
            action: 'hns_run_fix',
            nonce:  HNS.nonce,
            fix:    fix,
        }, function (res) {
            if (res.success) {
                $msg.addClass('ok').text('✓ ' + res.data.msg).show();
                $step.removeClass('fail').addClass('pass');
                $step.find('.hns-step-icon').text('✓');
                $btn.remove();
                updateScore();
            } else {
                $msg.addClass('err').text('✗ ' + (res.data ? res.data.msg : 'Unknown error')).show();
                $btn.prop('disabled', false).text($btn.data('orig-label') || 'Retry');
            }
        }).fail(function () {
            $msg.addClass('err').text('✗ Request failed. Please try again.').show();
            $btn.prop('disabled', false);
        });
    });

    // cache original labels
    $('.hns-fix-btn').each(function () {
        $(this).data('orig-label', $(this).text());
    });

    function updateScore() {
        var total  = $('.hns-step').length;
        var passed = $('.hns-step.pass').length;
        var pct    = Math.round(passed / total * 100);
        var color  = pct >= 80 ? '#1D9E75' : (pct >= 50 ? '#BA7517' : '#E24B4A');

        $('.hns-score-num').html(pct + '<small>%</small>');
        $('.hns-score-ring').css({
            '--pct': pct,
            '--clr': color,
        });
        $('.hns-score-detail').text(passed + ' / ' + total + ' checks passing');
        var label = pct >= 80 ? 'Good' : (pct >= 50 ? 'Needs work' : 'At risk');
        $('.hns-score-label').text(label).css('color', color);
    }
});
