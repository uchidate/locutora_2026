(function (blocks, blockEditor, components, element, i18n, serverSideRender) {
  const el = element.createElement;
  const Fragment = element.Fragment;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const TextareaControl = components.TextareaControl;
  const ServerSideRender = serverSideRender;
  const __ = i18n.__;

  function editor(fields) {
    return function Edit(props) {
      const controls = fields.map(function (field) {
        const Control = field.multiline ? TextareaControl : TextControl;
        return el(Control, {
          key: field.name,
          label: field.label,
          value: props.attributes[field.name] || '',
          rows: field.rows || 4,
          onChange: function (value) {
            const update = {};
            update[field.name] = value;
            props.setAttributes(update);
          },
        });
      });

      return el(
        Fragment,
        null,
        el(InspectorControls, null, el(PanelBody, { title: __('Conteúdo da seção', 'locutora'), initialOpen: true }, controls)),
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
        { name: 'content', label: 'Conteúdo HTML', multiline: true, rows: 18 },
        { name: 'buttonLabel', label: 'Texto do botão' },
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
      edit: editor(definition.fields),
      save: function () { return null; },
    });
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
