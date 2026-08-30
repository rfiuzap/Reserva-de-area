document.querySelectorAll('a[href*="type=subgrupo&toggle="]').forEach((toggleLink) => {
    const parameters = new URL(toggleLink.href).searchParams;
    const subgroupId = parameters.get('toggle');
    const groupId = new URLSearchParams(window.location.search).get('editar_grupo');

    if (!subgroupId || !groupId) {
        return;
    }

    const editLink = document.createElement('a');
    editLink.className = 'btn btn-sm btn-action btn-action-edit';
    editLink.href = `editar_subgrupo.php?id=${encodeURIComponent(subgroupId)}`;
    editLink.textContent = 'Editar';
    toggleLink.before(editLink);
});
