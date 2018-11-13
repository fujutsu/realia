(function($){

    $(document).ready(function(){

        var relatedSection = $('#deco_manual_agents');
        var openModalBtn = relatedSection.find('#deco_open_find_agents_button');
        $('.agents-modal').length ? $('.agents-modal') : $('body').append(initRelatedModal());
        var relatedModal = $('.agents-modal');
        var existRelatedInp = $('#deco_users_ids');
        var existRelatedArr = existRelatedInp.length != 0 ? existRelatedInp.val().split(',') : [];
        var searchTable = relatedModal.find('.agents-modal__table tbody');
        var addedList = $('#ul_agents');

        // open modal
        openModalBtn.click(function(e){
            e.preventDefault();

            if (!relatedModal.length) {
                relatedModal = $('body').append(initRelatedModal());
            } else {
                relatedModal.find('.agents-modal__inp').val('');
                relatedModal.find('.agents-modal__select-block').hide();
                searchTable.html('');
            }

            relatedModal.fadeIn();

        });



        // close modal
        relatedModal.click(function(e){
            if ($(this).has(e.target).length === 0) {
                relatedModal.fadeOut();
            }
        });

        $(document).on('keydown', function(e){
            if (e.which == 27) {
                relatedModal.fadeOut();
            }
        });


        // search
        relatedModal.on('submit', '.agents-modal__form', function(e){
            e.preventDefault();
            var search = $(this).find('.agents-modal__inp').val();

            $.ajax({
                url     : deco_agents.ajaxurl,
                type    : "POST",
                dataType: 'json',
                data    : {
                    action   : 'deco_find_agents',
                    s        : search
                },
                beforeSend: function(){
                    relatedModal.find('.agents-modal__preloader').fadeIn();
                },
                success: function(data) {
                    //console.log(data);

                    if (data.status==204) {
                        searchTable.html('<tr><td>Ничего не найдено.</td></tr>');
                        relatedModal.find('.agents-modal__select-block').hide();
                    } else {
                        initPostTable(searchTable, data.users, existRelatedArr);
                    }
                    relatedModal.find('.agents-modal__preloader').fadeOut();
                }
            });
        });

        // add from list
        relatedModal.on('click', '.agents-modal__select', function(e){
            e.preventDefault();
            relatedModal.find('.agents-modal__table tr:not(.addedPost) input[type="checkbox"]:checked').each(function(){
                var id = $(this).data('post-id');
                var avatar = $(this).closest('td').next().html();
                var title = $(this).closest('td').next().next().text();
                var cost = $(this).closest('tr').find('[name="price"]').val() || 0;
                $(this).closest('tr').addClass("addedPost");
                existRelatedArr.push(id);
                addedList.append(
                    '<li data-id="' + id + '" class="offer-agent">' +
                        '<p class="offer-agent-avatar">' + avatar + '</p>' +
                        '<p class="offer-agent-meta">' +
                            '<span>' + title + '</span>' +
                            '<span class="cost">' + cost + '</span>' +
                        '</p>' +
                        '<p>' +
                            '<a class="erase_yyarpp">X</a></span>' +
                        '</p>' +
                    '</li>'
                );
                $(this).closest('tr').hide();
            });
            //existRelatedInp.val(existRelatedArr.join());
            existRelatedInp.val(getAgents());
        });

        // clear list
        $('#deco_delete_agents').click(function(e){
            e.preventDefault();
            $(this).closest('.inside').find('#ul_agents').html('');
            existRelatedInp.val('');
            existRelatedArr = [];
        });

        // remove from list
        addedList.on('click', '.erase_yyarpp', function(e){
            e.preventDefault();
            var liItem = $(this).closest('li');
            var id = liItem.data('id');
            liItem.remove();
            existRelatedArr.splice(existRelatedArr.indexOf(id.toString()), 1);
            //existRelatedInp.val(existRelatedArr.join());
            existRelatedInp.val(getAgents());
        });

    });

    function initRelatedModal(){
        return '<div class="agents-modal">'+
                    '<div class="agents-modal__cont">' +
                        '<h2 class="agents-modal__title">Найти агентов</h2>' +
                        '<form class="agents-modal__form">' +
                            '<input type="text" name="s" class="agents-modal__inp">' +
                            '<input type="submit" class="agents-modal__subm button button-small" value="Поиск">' +
                        '</form>' +
                        '<div class="agents-modal__table">' +
                            '<table>' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th></th>' +
																				'<th></th>' +
                                        '<th>Имя</th>' +
																				'<th>Цена</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody></tbody>' +
                            '</table>' +
                            '<div class="agents-modal__select-block">' +
                                '<button class="agents-modal__select button button-primary">Выбрать</button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="agents-modal__preloader"></div>' +
                    '</div>' +
                '</div>';
    }

    function initPostTable(table, data, existArr){
        var output = '';
        data.forEach(function(item){
            var added = false;
            existArr.forEach(function(id){
                if (id==item.ID) {
                    added = true;
                }
            });
            output += '<tr class="' + (added ? 'addedPost' : '') + '">' +
                        '<td> <input type="checkbox" data-post-id="' + item.ID + '" ' + (added ? 'checked' : '') + '> </td>' +
                        '<td>' + item.avatar + '</td>' +
                        '<td>' + item.name + '</td>' +
                        '<td><input name="price"></td>' +
                    '</tr>';
        });

        table.html(output);
        table.closest('table').siblings('.agents-modal__select-block').show();
    }

    function getAgents(){
        var agentsBlock = $('#deco_manual_agents');
        var agentsList = agentsBlock.find('#ul_agents');
        var agentsArray = [];

        agentsList.find('li').each(function(){
            var agentID = $(this).data('id');
            var cost = $(this).find('.cost').text();

            agentsArray.push({'id': agentID, 'cost': cost});

        });

        return JSON.stringify(agentsArray);

    }

})(jQuery);