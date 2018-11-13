var Deco_Xml_Analysis = {

    analyse_xml: function () {
        var button = jQuery('.cmb2-id-xml-list-xml-analysis'),
            post_id = jQuery('input[name="post_ID"]').val(),
            xml_link = jQuery('textarea[name="xml_list_link"]').val(),
            feed_standard = jQuery('select[name="xml_list_feed_standard"]').val(),
            custom_fields_heading = jQuery('.cmb2-id-xml-list-custom-field'),
            custom_fields_heading_row = custom_fields_heading.parents('.cmb2GridRow'),
            data = [],
            html = '';

        if (xml_link) {
            jQuery.ajax({
                url: '/wp-admin/admin-ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'deco_xml_analysis',
                    post_id: post_id,
                    xml_link: xml_link,
                    feed_standard: feed_standard
                },
                success: function (response) {
                    data = response.data;
                    for (var i in data) {
                        html += '<div class="row cmb2GridRow">' +
                            '<div class="col-md-3"><div class="cmb-row cmb-type-text cmb2-id-xml-list-custom-field-input table-layout" data-fieldtype="text">' +
                            '<div class="cmb-td">' +
                            '<input type="text" class="regular-text" name="xml_list_custom_field_' + i + '" id="xml_list_custom_field_' + i + '" value="' + data[i] + '" readonly="readonly">' +
                            '</div>' +
                            '</div></div><div class="col-md-4"><div class="cmb-row cmb-type-text cmb2-id-xml-list-custom-field-name-ua table-layout" data-fieldtype="text">' +
                            '<div class="cmb-td">' +
                            '<input type="text" class="regular-text" name="xml_list_custom_field_name_ua_' + i + '" id="xml_list_custom_field_name_ua_' + i + '" value="">' +
                            '</div>' +
                            '</div></div><div class="col-md-4"><div class="cmb-row cmb-type-text cmb2-id-xml-list-custom-field-name-ru table-layout" data-fieldtype="text">' +
                            '<div class="cmb-td">' +
                            '<input type="text" class="regular-text" name="xml_list_custom_field_name_ru_' + i + '" id="xml_list_custom_field_name_ru_' + i + '" value="">' +
                            '</div>' +
                            '</div></div><div class="col-md-1"><div class="cmb-row cmb-type-checkbox cmb2-id-xml-list-custom-field-checkbox" data-fieldtype="checkbox">' +
                            '<div class="cmb-td">' +
                            '<input type="checkbox" class="cmb2-option cmb2-list" name="xml_list_custom_field_checkbox_' + i + '" id="xml_list_custom_field_checkbox_' + i + '" value="on"><label for="xml_list_custom_field_checkbox_' + i + '"></label>' +
                            '</div>' +
                            '</div></div></div>';
                    }
                    html += '<input type="hidden" name="xml_list_custom_fields_count" value="' + (parseInt(i) + 1) + '">';
                    custom_fields_heading_row.after(html);
                    button.remove();
                }
            });
        }
    },

    init: function () {
        jQuery(document).on('click', '.xml-analysis', function (e) {
            e.preventDefault();
            Deco_Xml_Analysis.analyse_xml();
        });
    }

};

jQuery(document).on('ready', function () {
    Deco_Xml_Analysis.init();
});