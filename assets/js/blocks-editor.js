(function (blocks, blockEditor, components, element, i18n, serverSideRender) {
  const el = element.createElement;
  const Fragment = element.Fragment;
  const useState = element.useState;
  const BlockControls = blockEditor.BlockControls;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const BaseControl = components.BaseControl;
  const Button = components.Button;
  const Notice = components.Notice;
  const ToolbarButton = components.ToolbarButton;
  const ToolbarGroup = components.ToolbarGroup;
  const TextControl = components.TextControl;
  const TextareaControl = components.TextareaControl;
  const RichText = blockEditor.RichText;
  const MediaUpload = blockEditor.MediaUpload;
  const MediaUploadCheck = blockEditor.MediaUploadCheck;
  const ServerSideRender = serverSideRender;
  const __ = i18n.__;

  function fieldControl(field, props, compact) {
    const value = props.attributes[field.name] || (field.gallery ? [] : '');
    const update = function (nextValue) {
      const attributes = {};
      attributes[field.name] = nextValue;
      props.setAttributes(attributes);
    };

    if (field.gallery) {
      return el(
        BaseControl,
        { key: field.name, label: field.label, className: 'locutora-editor-field locutora-editor-field--gallery' },
        value.length ? el('div', { className: 'locutora-editor-gallery' }, value.map(function (url, index) {
          return el('div', { key: url + index, className: 'locutora-editor-gallery__item' },
            el('img', { src: url, alt: '' }),
            el(Button, {
              icon: 'no-alt',
              label: __('Remover logotipo', 'locutora'),
              isDestructive: true,
              onClick: function () { update(value.filter(function (_, itemIndex) { return itemIndex !== index; })); },
            })
          );
        })) : el('p', null, __('As imagens padrão do tema estão em uso.', 'locutora')),
        el(MediaUploadCheck, null,
          el(MediaUpload, {
            allowedTypes: ['image'],
            multiple: true,
            gallery: true,
            onSelect: function (media) { update(media.map(function (item) { return item.url; })); },
            render: function (mediaProps) { return el(Button, { variant: 'secondary', onClick: mediaProps.open }, __('Selecionar logotipos', 'locutora')); },
          })
        ),
        value.length ? el(Button, { variant: 'tertiary', isDestructive: true, onClick: function () { update([]); } }, __('Usar logotipos padrão', 'locutora')) : null
      );
    }

    if (field.media) {
      return el(
        BaseControl,
        { key: field.name, label: field.label, className: 'locutora-editor-field locutora-editor-field--media' },
        value && (!field.allowedTypes || field.allowedTypes.indexOf('image') !== -1)
          ? el('img', { src: value, alt: '', className: 'locutora-editor-media-preview' })
          : value ? el('code', { className: 'locutora-editor-media-url' }, value) : null,
        el(MediaUploadCheck, null,
          el(MediaUpload, {
            allowedTypes: field.allowedTypes || ['image'],
            value: 0,
            onSelect: function (media) { update(media.url || ''); },
            render: function (mediaProps) {
              return el(Button, { variant: 'secondary', onClick: mediaProps.open }, value ? __('Trocar arquivo', 'locutora') : __('Escolher arquivo', 'locutora'));
            },
          })
        ),
        value ? el(Button, { variant: 'tertiary', isDestructive: true, onClick: function () { update(''); } }, __('Usar arquivo padrão', 'locutora')) : null
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
      type: field.url ? 'url' : 'text',
      help: field.help || undefined,
      rows: field.rows || 4,
      onChange: update,
    });
  }

  function editor(fields, title, alwaysEdit) {
    return function Edit(props) {
      const state = useState('edit');
      const mode = state[0];
      const setMode = state[1];
      const controls = fields.map(function (field) { return fieldControl(field, props, false); });
      const sidebarControls = fields.filter(function (field) { return !field.richtext; }).map(function (field) { return fieldControl(field, props, true); });

      return el(
        Fragment,
        null,
        el(BlockControls, null,
          el(ToolbarGroup, null,
            el(ToolbarButton, { icon: 'edit', label: __('Editar conteúdo', 'locutora'), isPressed: mode === 'edit', onClick: function () { setMode('edit'); } }),
            el(ToolbarButton, { icon: 'visibility', label: __('Ver prévia', 'locutora'), isPressed: mode === 'preview', onClick: function () { setMode('preview'); } })
          )
        ),
        el(InspectorControls, null, el(PanelBody, { title: __('Conteúdo da seção', 'locutora'), initialOpen: true }, sidebarControls)),
        (alwaysEdit || props.isSelected) && mode === 'edit' ? el(
          'div',
          { className: 'locutora-block-editor' },
          el('h3', { className: 'locutora-block-editor__title' }, __('Editar: ', 'locutora') + title),
          el(Notice, { status: 'info', isDismissible: false }, __('Altere os campos abaixo e use Atualizar para publicar.', 'locutora')),
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
        { name: 'buttonUrl', label: 'Destino do botão' },
        { name: 'portraitUrl', label: 'Foto da locutora', media: true },
        { name: 'portraitAlt', label: 'Descrição da foto' },
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
        { name: 'servicesUrl', label: 'Destino dos serviços' },
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
        { name: 'buttonUrl', label: 'Destino do botão' },
        { name: 'videoUrl', label: 'Vídeo de fundo', media: true, allowedTypes: ['video'] },
      ],
    },
    {
      name: 'locutora/internal-hero',
      title: 'Locutora — Cabeçalho interno',
      icon: 'cover-image',
      fields: [
        { name: 'title', label: 'Título da página' },
        { name: 'backgroundUrl', label: 'Imagem de fundo', media: true },
      ],
    },
    {
      name: 'locutora/about-story',
      title: 'Locutora — História',
      icon: 'book-alt',
      fields: [
        { name: 'title', label: 'Título', multiline: true, rows: 2 },
        { name: 'missionTitle', label: 'Título: Missão' },
        { name: 'missionText', label: 'Texto da missão', multiline: true, rows: 4 },
        { name: 'visionTitle', label: 'Título: Visão' },
        { name: 'visionText', label: 'Texto da visão', multiline: true, rows: 4 },
        { name: 'valuesTitle', label: 'Título: Valores' },
        { name: 'valuesText', label: 'Texto dos valores', multiline: true, rows: 4 },
        { name: 'imageUrl', label: 'Imagem do estúdio', media: true },
        { name: 'imageAlt', label: 'Descrição da imagem' },
      ],
    },
    {
      name: 'locutora/about-bio',
      title: 'Locutora — Biografia',
      icon: 'admin-users',
      fields: [
        { name: 'title', label: 'Nome' },
        { name: 'paragraph1', label: 'Biografia — parágrafo 1', multiline: true, rows: 5 },
        { name: 'paragraph2', label: 'Biografia — parágrafo 2', multiline: true, rows: 5 },
        { name: 'paragraph3', label: 'Biografia — parágrafo 3', multiline: true, rows: 5 },
        { name: 'paragraph4', label: 'Biografia — parágrafo 4', multiline: true, rows: 5 },
        { name: 'paragraph5', label: 'Biografia — parágrafo 5', multiline: true, rows: 5 },
        { name: 'paragraph6', label: 'Biografia — parágrafo 6', multiline: true, rows: 5 },
        { name: 'imageUrl', label: 'Retrato', media: true },
        { name: 'imageAlt', label: 'Descrição do retrato' },
      ],
    },
    {
      name: 'locutora/brands',
      title: 'Locutora — Marcas',
      icon: 'grid-view',
      fields: [
        { name: 'title', label: 'Título' },
        { name: 'images', label: 'Logotipos', gallery: true },
      ],
    },
    {
      name: 'locutora/audio-showcase',
      title: 'Locutora — Áudios e vídeos',
      icon: 'playlist-audio',
      fields: [
        { name: 'title', label: 'Título da seção', multiline: true, rows: 3, help: 'Use uma nova linha para controlar a quebra do título.' },
        { name: 'soundcloudUrl', label: 'Link do SoundCloud', url: true, help: 'Cole o link normal do perfil, faixa ou playlist. O player é criado automaticamente.' },
        { name: 'youtubeUrl', label: 'Link do YouTube', url: true, help: 'Cole o link normal de um vídeo ou playlist. O player é criado automaticamente.' },
        { name: 'backgroundUrl', label: 'Imagem de fundo', media: true },
      ],
      alwaysEdit: true,
    },
    {
      name: 'locutora/contact-form',
      title: 'Locutora — Formulário de contato',
      icon: 'feedback',
      fields: [
        { name: 'nameLabel', label: 'Rótulo: nome' },
        { name: 'emailLabel', label: 'Rótulo: e-mail' },
        { name: 'phoneLabel', label: 'Rótulo: telefone' },
        { name: 'subjectLabel', label: 'Rótulo: assunto' },
        { name: 'messageLabel', label: 'Rótulo: mensagem' },
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
      edit: editor(definition.fields, definition.title, definition.alwaysEdit === true),
      save: function () { return null; },
    });
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
