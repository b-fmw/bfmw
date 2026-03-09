/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

function bfmwTreeViewSetState(branch, expanded) {
    branch.classList.toggle('bfmw_treeview_branch_open', expanded);
    branch.classList.toggle('bfmw_treeview_branch_closed', !expanded);
    branch.setAttribute('aria-expanded', expanded ? 'true' : 'false');

    const subList = branch.querySelector(':scope > ul');
    if (!subList) {
        return;
    }

    subList.hidden = !expanded;
}

function bfmwTreeViewToggleBranch(branch) {
    const isExpanded = branch.classList.contains('bfmw_treeview_branch_open');
    bfmwTreeViewSetState(branch, !isExpanded);
}

function bfmwTreeViewEnsureLabel(node, stopNode = null) {
    if (!node || node.querySelector(':scope > .bfmw_treeview_label')) {
        return;
    }

    const label = document.createElement('span');
    label.classList.add('bfmw_treeview_label');

    const nodesToMove = [];
    node.childNodes.forEach((child) => {
        if (child === stopNode) {
            return;
        }

        if (child.nodeType === Node.ELEMENT_NODE && child.tagName === 'UL') {
            return;
        }

        nodesToMove.push(child);
    });

    if (!nodesToMove.length) {
        return;
    }

    nodesToMove.forEach((child) => {
        label.appendChild(child);
    });

    node.insertBefore(label, stopNode || node.firstChild);
}


function bfmwTreeViewClickedOnMarker(node, event) {
    if (!node || !event || event.target !== node) {
        return false;
    }

    const markerWidth = parseFloat(window.getComputedStyle(node, '::before').width) || 0;
    if (markerWidth <= 0) {
        return false;
    }

    const clickOffset = event.clientX - node.getBoundingClientRect().left;
    return clickOffset <= markerWidth;
}

function bfmwTreeViewInitialize(root, collapseByDefault = true) {
    if (!root) {
        return;
    }

    const branches = root.querySelectorAll('li');

    branches.forEach((branch) => {
        const subList = branch.querySelector(':scope > ul');
        const hasChildren = !!subList;

        branch.classList.add('bfmw_treeview_node');
        bfmwTreeViewEnsureLabel(branch, subList);

        if (!hasChildren) {
            branch.classList.add('bfmw_treeview_leaf');
            return;
        }

        branch.classList.add('bfmw_treeview_branch');
        branch.classList.add('bfmw_treeview_interactive');
        branch.tabIndex = 0;

        if (collapseByDefault) {
            bfmwTreeViewSetState(branch, false);
        } else {
            bfmwTreeViewSetState(branch, true);
        }

        branch.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, textarea, select, label')) {
                return;
            }

            const clickedBranch = event.target.closest('li');
            if (clickedBranch !== branch) {
                return;
            }

            const clickedLabel = event.target.closest('.bfmw_treeview_label');
            if (!clickedLabel && !bfmwTreeViewClickedOnMarker(branch, event)) {
                return;
            }

            bfmwTreeViewToggleBranch(branch);
        });

        branch.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                bfmwTreeViewToggleBranch(branch);
            }
        });
    });
}

function bfmwTreeViewAutoHideInitialize(container) {
    const autoHideTriggers = container.querySelectorAll('.bfmw_auto_hidder');

    autoHideTriggers.forEach((trigger) => {
        bfmwTreeViewEnsureLabel(trigger);
        trigger.classList.add('bfmw_treeview_interactive', 'bfmw_treeview_branch', 'bfmw_treeview_branch_closed');
        trigger.tabIndex = 0;

        const targetId = trigger.dataset.bfmw;
        if (!targetId) {
            return;
        }

        const targetById = document.getElementById(targetId);
        const targetByClass = targetById ? null : container.querySelectorAll(`.${targetId}`);

        const targets = targetById
            ? [targetById]
            : Array.from(targetByClass || []);

        targets.forEach((target) => {
            target.classList.add('hide');
        });

        const toggle = function () {
            const isClosed = trigger.classList.contains('bfmw_treeview_branch_closed');
            trigger.classList.toggle('bfmw_treeview_branch_closed', !isClosed);
            trigger.classList.toggle('bfmw_treeview_branch_open', isClosed);
            trigger.setAttribute('aria-expanded', isClosed ? 'true' : 'false');

            targets.forEach((target) => {
                target.classList.toggle('hide', !isClosed);
            });
        };

        trigger.addEventListener('click', function (event) {
            const clickedLabel = event.target.closest('.bfmw_treeview_label');
            if (!clickedLabel && !bfmwTreeViewClickedOnMarker(trigger, event)) {
                return;
            }

            toggle();
        });
        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggle();
            }
        });
    });
}

toDoOnLoad(function () {
    document.querySelectorAll('[data-bfmw-treeview]').forEach((tree) => {
        bfmwTreeViewInitialize(tree, tree.dataset.bfmwTreeview !== 'expanded');
    });

    bfmwTreeViewAutoHideInitialize(document);
});
