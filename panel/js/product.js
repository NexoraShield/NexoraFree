window.openEditModal = function (p) {
    document.getElementById('edit_id').value = p.id || '';
    document.getElementById('edit_name').value = p.name_product || '';
    document.getElementById('edit_price').value = p.price_product || '';
    document.getElementById('edit_volume').value = p.Volume_constraint || '';
    document.getElementById('edit_time').value = p.Service_time || '';
    document.getElementById('edit_cat').value = p.category || '';
    document.getElementById('edit_agent').value = p.agent || '';
    document.getElementById('edit_note').value = p.note || '';
    document.getElementById('edit_position').value = p.position || '';

    // the account of the panel that shows the group of the product
    var proto = document.getElementById('edit_protocol');
    if (proto) {
        proto.value = p.protocol_account || '';
    }

    // The panel of the product: the location of the row is the name of the
    // panel.
    var sel = document.getElementById('edit_panel');
    if (sel) {
        var location = p.Location || p.namepanel || '';
        for (var i = 0; i < sel.options.length; i++) {
            sel.options[i].selected = sel.options[i].value === location;
        }
    }


    openModal('editModal');
};
