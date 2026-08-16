<div class="grid-dropdown-actions">
    <a href="#" class="grid-action-toggle">
        <i class="fa fa-ellipsis-v"></i>
    </a>
    <ul class="dropdown-menu grid-dropdown-menu">
        @foreach($default as $action)
            <li>{!! $action->render() !!}</li>
        @endforeach

        @if(!empty($custom))
            @if(!empty($default))
                <li class="divider"></li>
            @endif

            @foreach($custom as $action)
                <li>{!! $action->render() !!}</li>
            @endforeach
        @endif
    </ul>
</div>

<style>
    .grid-dropdown-actions .grid-action-toggle {
        padding: 0 10px;
        cursor: pointer;
    }

    .grid-dropdown-menu {
        min-width: 70px !important;
        box-shadow: 0 2px 3px 0 rgba(0,0,0,.2);
        border-radius: 0;
        top: auto;
    }

    .ltr .grid-dropdown-menu{
        left: -65px;
    }

    .rtl .grid-dropdown-menu{
        right: -65px;
    }

    .grid-dropdown-menu .divider {
        height: 1px;
        margin: 8px 0;
        overflow: hidden;
        background-color: #e5e5e5;
    }
</style>

<script>
(function(){
    if (window._gridActionToggleBound) return;
    window._gridActionToggleBound = true;

    $(document).on('click', '.grid-action-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $w = $(this).closest('.grid-dropdown-actions');
        var $menu = $w.find('.dropdown-menu');
        var isOpen = $w.data('menu-open');

        // Close any existing
        $('body > .grid-dropdown-menu-clone').remove();
        $('.grid-dropdown-actions').data('menu-open', false);

        if (isOpen) return;

        var r = this.getBoundingClientRect();
        var $c = $menu.clone(true).addClass('grid-dropdown-menu-clone').appendTo('body');
        var mh = $c.outerHeight() || 200, mw = $c.outerWidth() || 160;
        var wh = $(window).height(), ww = $(window).width();
        var up = (r.bottom + mh + 10 > wh) && (r.top > mh);
        var l = r.right - mw;
        if (l < 5) l = 5;
        if (l + mw > ww) l = ww - mw - 5;
        var t = up ? (r.top - mh - 2) : (r.bottom + 2);

        $c.css({
            display: 'block',
            position: 'fixed',
            top: t + 'px',
            left: l + 'px',
            zIndex: 99999,
            minWidth: '160px',
            background: '#fff',
            borderRadius: '4px',
            border: '1px solid rgba(0,0,0,.15)',
            boxShadow: '0 6px 20px rgba(0,0,0,.18)',
            padding: '5px 0',
            listStyle: 'none'
        });
        $w.data('menu-open', true);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.grid-action-toggle,.grid-dropdown-menu-clone').length) {
            $('body > .grid-dropdown-menu-clone').remove();
            $('.grid-dropdown-actions').data('menu-open', false);
        }
    });

    $(window).on('scroll', function() {
        $('body > .grid-dropdown-menu-clone').remove();
        $('.grid-dropdown-actions').data('menu-open', false);
    });
})();
</script>

@yield('child')
