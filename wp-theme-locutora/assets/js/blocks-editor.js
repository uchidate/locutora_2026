(function (blocks, blockEditor, components, element, i18n, serverSideRender) {
  const el = element.createElement;
  const Fragment = element.Fragment;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const BaseControl = components.BaseControl;
  const Button = components.Button;
  const TextControl = components.TextControl;
  const TextareaControl = components.TextareaControl;
  const RichText = blockEditor.RichText;
  const MediaUpload = blockEditor.MediaUpload;
  const MediaUploadCheck = blockEditor.MediaUploadCheck;
  const ServerSideRender = serverSideRender;
  const __ = i18n.__;

  function fieldControl(field, props, compact) {
    const value = props.attributes[field.name] || '';
    const update = function (nextValue) {
      const attributes = {};
      attributes[field.name] = nextValue;
      props.setAttributes(attributes);
    };

    if (field.media) {
      return el(
        BaseControl,
        { key: field.name, label: field.label, className: 'locutora-editor-field locutora-editor-field--media' },
        value ? el('img', { src: value, alt: '', className: 'locutora-editor-media-preview' }) : null,
        el(MediaUploadCheck, null,
          el(MediaUpload, {
            allowedTypes: ['image'],
            value: 0,
            onSelect: function (media) { update(media.url || ''); },
            render: function (mediaProps) {
              return el(Button, { variant: 'secondary', onClick: mediaProps.open }, value ? __('Trocar imagem', 'locutora') : __('Escolher imagem', 'locutora'));
            },
          })
        ),
        value ? el(Button, { variant: 'tertiary', isDestructive: true, onClick: function () { update(''); } }, __('Usar imagem padrão', 'locutora')) : null
      );
    }

    if (field.richtext && !compact) {
      return el(
        BaseControl,
        { key: field.name, label: field.label, className: 'locutora-editor-field locutora-editor-field--richtext' },
        el(RichText, {
          tagName: 'div',
          value: value,
          onChange: update,
          allowedFormats: ['core/bold', 'core/italic', 'core/link'],
          placeholder: field.label,
        })
      );
    }

    const Control = field.multiline ? TextareaControl : TextControl;
    return el(Control, {
      key: field.name,
      label: field.label,
      value: value,
      rows: field.rows || 4,
      onChange: update,
    });
  }

  function editor(fields, title) {
    return function Edit(props) {
      const controls = fields.map(function (field) { return fieldControl(field, props, false); });
      const sidebarControls = fields.filter(function (field) { return !field.richtext; }).map(function (field) { return fieldControl(field, props, true); });

      return el(
        Fragment,
        null,
        el(InspectorControls, null, el(PanelBody, { title: __('Conteúdo da seção', 'locutora'), initialOpen: true }, sidebarControls)),
        props.isSelected ? el(
          'div',
          { className: 'locutora-block-editor' },
          el('h3', { className: 'locutora-block-editor__title' }, __('Editar: ', 'locutora') + title),
          el('div', { className: 'locutora-block-editor__fields' }, controls)
        ) : null,
        el(ServerSideRender, { block: props.name, attributes: props.attributes })
      );
    };
  }

  const definitions = [
    {
      name: 'locutora/hero',
      title: 'Locutora — Hero',
      icon: 'format-video',
      fields: [
        { name: 'eyebrow', label: 'Linha superior' },
        { name: 'title', label: 'Título' },
        { name: 'subtitle', label: 'Subtítulo' },
      ],
    },
    {
      name: 'locutora/intro',
      title: 'Locutora — Apresentação',
      icon: 'welcome-write-blog',
      fields: [
        { name: 'title', label: 'Título' },
        { name: 'content', label: 'Texto da apresentação', richtext: true },
        { name: 'buttonLabel', label: 'Texto do botão' },
        { name: 'portraitUrl', label: 'Foto da locutora', media: true },
      ],
    },
    {
      name: 'locutora/services',
      title: 'Locutora — Serviços',
      icon: 'megaphone',
      fields: [
        { name: 'title', label: 'Título' },
        { name: 'item1', label: 'Serviço 1' },
        { name: 'item2', label: 'Serviço 2' },
        { name: 'item3', label: 'Serviço 3' },
        { name: 'item4', label: 'Serviço 4' },
        { name: 'icon1Url', label: 'Ícone do serviço 1', media: true },
        { name: 'icon2Url', label: 'Ícone do serviço 2', media: true },
        { name: 'icon3Url', label: 'Ícone do serviço 3', media: true },
        { name: 'icon4Url', label: 'Ícone do serviço 4', media: true },
      ],
    },
    {
      name: 'locutora/contact-cta',
      title: 'Locutora — Chamada de contato',
      icon: 'email-alt',
      fields: [
        { name: 'heading', label: 'Chamada', multiline: true, rows: 3 },
        { name: 'buttonLabel', label: 'Texto do botão' },
      ],
    },
  ];

  definitions.forEach(function (definition) {
    blocks.registerBlockType(definition.name, {
      apiVersion: 3,
      title: definition.title,
      icon: definition.icon,
      category: 'design',
      supports: { html: false, reusable: false },
      edit: editor(definition.fields, definition.title),
      save: function () { return null; },
    });
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
