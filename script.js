jQuery(document).ready(function($) {

    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + encodeURIComponent(value || "") + expires + "; path=/; SameSite=Lax";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
        return null;
    }

    function loadGroups() {
        var league = $('#olwp-league').val();
        var season = $('#olwp-season').val();
        var $groupSelect = $('#olwp-group');

        setCookie('olwp_league', league, 30);
        setCookie('olwp_season', season, 30);

        if (!league) {
            $groupSelect.html('<option>(Erst Liga waehlen)</option>').prop('disabled', true);
            return;
        }

        $groupSelect.html('<option>Lade Spieltage...</option>').prop('disabled', true);

        $.post(olwp_vars.ajax_url, {
            action: 'olwp_get_groups',
            league: league,
            season: season,
            nonce: olwp_vars.nonce
        }, function(response) {
            if (response.success) {
                $groupSelect.empty().prop('disabled', false);

                var groups = response.data.list;
                var currentId = response.data.currentId;

                if (groups && groups.length > 0) {
                    $.each(groups, function(index, group) {
                        $groupSelect.append(
                            $('<option></option>').attr('value', group.groupOrderID).text(group.groupName)
                        );
                    });

                    var targetValue = groups[0].groupOrderID;

                    if (currentId && $groupSelect.find("option[value='" + currentId + "']").length > 0) {
                        targetValue = currentId;
                    }

                    $groupSelect.val(targetValue);
                    loadData();
                } else {
                    $groupSelect.html('<option>Keine Spieltage</option>');
                }
            } else {
                $groupSelect.html('<option>Fehler beim Laden</option>');
                if (console && console.error) {
                    console.error('OpenLigaWP:', response.data ? response.data.message : 'Unknown error');
                }
            }
        }).fail(function() {
            $groupSelect.html('<option>Fehler beim Laden</option>');
        });
    }

    function loadData() {
        var league = $('#olwp-league').val();
        var season = $('#olwp-season').val();
        var group = $('#olwp-group').val();
        var $content = $('#olwp-content');

        if (!league || !group) return;

        $content.css('opacity', '0.5');

        $.post(olwp_vars.ajax_url, {
            action: 'olwp_load_data',
            league: league,
            season: season,
            group_order_id: group,
            nonce: olwp_vars.nonce
        }, function(response) {
            $content.css('opacity', '1');
            if (response.success) {
                $content.html(response.data.html);
            } else {
                $content.html('<div class="olwp-msg">Daten konnten nicht geladen werden.</div>');
                if (console && console.error) {
                    console.error('OpenLigaWP:', response.data ? response.data.message : 'Unknown error');
                }
            }
        }).fail(function() {
            $content.css('opacity', '1');
            $content.html('<div class="olwp-msg">Daten konnten nicht geladen werden.</div>');
        });
    }

    $('#olwp-league').on('change', loadGroups);
    $('#olwp-season').on('change', loadGroups);
    $('#olwp-group').on('change', loadData);
    $('#olwp-refresh').on('click', loadData);

    var savedLeague = getCookie('olwp_league');
    var savedSeason = getCookie('olwp_season');

    if (savedLeague) {
        $('#olwp-league').val(savedLeague);

        if (savedSeason && $('#olwp-season option[value="' + savedSeason + '"]').length > 0) {
            $('#olwp-season').val(savedSeason);
        }
        loadGroups();
    }
});
