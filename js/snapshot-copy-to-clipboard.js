Drupal.behaviors.wmcontent_snapshot_copy_to_clipboard = {
    attach: function () {
        const textarea = document.querySelector('.wmcontent-snapshot-export-to-clipboard--blob');
        const button = document.querySelector('.wmcontent-snapshot-export-to-clipboard--button');
        const message = document.querySelector('.wmcontent-snapshot-export-to-clipboard--msg');

        if (
            !textarea || !button || !message
        ) {
            return;
        }

        button.addEventListener('click', (e) => {
            e.preventDefault();

            textarea.select();
            document.execCommand('copy');

            message.classList.remove('hidden');
            return false;
        });
    }
};
