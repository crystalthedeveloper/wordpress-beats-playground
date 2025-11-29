(function(blocks, element, i18n) {
  const { registerBlockType } = blocks;
  const { createElement: el } = element;
  const { __ } = i18n;

  function renderPreview(key, label) {
    const previews = window.BeatsBlockPreviews || {};
    const src = previews[key];
    if (src) {
      return el('div', { className: 'beats-block-preview-wrapper' }, el('img', { src, alt: label }));
    }
    return el('div', { className: 'beats-block-preview-wrapper' }, label);
  }

  registerBlockType('beats/display-home', {
    edit: function() {
      return renderPreview('beats/display-home', __('Display Home preview', 'beats-upload-player'));
    },
    save: function() { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n);
