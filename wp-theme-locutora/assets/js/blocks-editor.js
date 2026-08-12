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
  const SelectControl = components.SelectControl;
  const MediaUpload = blockEditor.MediaUpload;
  const MediaUploadCheck = blockEditor.MediaUploadCheck;
  const ServerSideRender = serverSideRender;
  const useEffect = element.useEffect;
  const useRef = element.useRef;
  const __ = i18n.__;

  const classicEditor = window.wp.oldEditor || window.wp.editor;
  const editorSettings = window.locutoraEditorSettings || {};
  let wysiwygCounter = 0;

  function tinymceSettings(onChange) {
    return {
      wpautop: true,
      menubar: false,
      branding: false,
      statusbar: false,
      browser_spellcheck: true,
      content_css: (editorSettings.contentCss || []).join(','),
      block_formats: __('Parágrafo=p;Título 2=h2;Título 3=h3;Título 4=h4;Citação=blockquote', 'locutora'),
      font_formats: 'Padrão do tema=inherit;Montserrat=Montserrat,Arial,sans-serif;Arial=Arial,Helvetica,sans-serif;Georgia=Georgia,serif;Times New Roman=Times New Roman,serif;Courier New=Courier New,monospace',
      fontsize_formats: '12px 14px 16px 18px 20px 24px 28px 32px 40px 48px',
      toolbar1: 'formatselect,fontselect,fontsizeselect,bold,italic,underline,forecolor,removeformat',
      toolbar2: 'bullist,numlist,blockquote,alignleft,aligncenter,alignright,alignjustify,outdent,indent,link,unlink,undo,redo',
      setup: function (tinymceEditor) {
        ['change', 'input', 'keyup', 'undo', 'redo', 'SetContent', 'blur'].forEach(function (eventName) {
          tinymceEditor.on(eventName, function () { onChange(); });
        });
      },
    };
  }

  function WysiwygField(props) {
    const field = props.field;
    const onChange = props.onChange;
    const initialValue = props.initialValue;
    const idRef = useRef(null);
    const textareaRef = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    if (idRef.current === null) {
      wysiwygCounter += 1;
      idRef.current = 'locutora-wysiwyg-' + wysiwygCounter;
    }
    const editorId = idRef.current;

    useEffect(function () {
      const textarea = textareaRef.current;
      if (!textarea || !classicEditor || typeof classicEditor.initialize !== 'function') {
        return undefined;
      }
      // O TinyMCE clássico só funciona fora do iframe do canvas (blocos apiVersion 2).
      if (textarea.ownerDocument !== window.document) {
        return undefined;
      }

      let timer = null;
      const pushChange = function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
          const next = classicEditor.getContent(editorId);
          if (typeof next === 'string') {
            onChangeRef.current(next);
          }
        }, 300);
      };

      classicEditor.initialize(editorId, {
        tinymce: tinymceSettings(pushChange),
        quicktags: { buttons: 'strong,em,link,ul,ol,li,block,h2,h3,close' },
        mediaButtons: false,
      });

      return function () {
        window.clearTimeout(timer);
        if (typeof classicEditor.remove === 'function') {
          classicEditor.remove(editorId);
        }
      };
    }, [editorId]);

    return el(
      BaseControl,
      { label: field.label, className: 'locutora-editor-field locutora-editor-field--wysiwyg', help: field.help || undefined },
      el('textarea', {
        id: editorId,
        ref: textareaRef,
        className: 'locutora-editor-wysiwyg wp-editor-area',
        rows: field.rows || 12,
        defaultValue: initialValue,
        onChange: function (event) { onChangeRef.current(event.target.value); },
      })
    );
  }

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

    if (field.wysiwyg && !compact && classicEditor && typeof classicEditor.initialize === 'function') {
      return el(WysiwygField, { key: field.name, field: field, initialValue: value, onChange: update });
    }

    if ((field.richtext || field.wysiwyg) && !compact) {
      return el(
        BaseControl,
        { key: field.name, label: field.label, className: 'locutora-editor-field locutora-editor-field--richtext' },
        el(RichText, {
          tagName: 'div',
          value: value,
          onChange: update,
          allowedFormats: ['core/bold', 'core/italic', 'core/link', 'core/strikethrough'],
          placeholder: field.label,
        })
      );
    }

    if (field.options) {
      return el(SelectControl, {
        key: field.name,
        label: field.label,
        value: value,
        options: field.options,
        onChange: update,
      });
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
      const sidebarControls = fields.filter(function (field) { return !field.richtext && !field.wysiwyg; }).map(function (field) { return fieldControl(field, props, true); });

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
      alwaysEdit: true,
      fields: [
        { name: 'eyebrow', label: 'Linha superior' },
        { name: 'title', label: 'Título', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
        { name: 'subtitle', label: 'Subtítulo' },
      ],
    },
    {
      name: 'locutora/intro',
      title: 'Locutora — Apresentação',
      icon: 'welcome-write-blog',
      alwaysEdit: true,
      fields: [
        { name: 'title', label: 'Título', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
        { name: 'content', label: 'Texto da apresentação', wysiwyg: true, rows: 16 },
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
      alwaysEdit: true,
      fields: [
        { name: 'title', label: 'Título', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
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
      alwaysEdit: true,
      fields: [
        { name: 'heading', label: 'Chamada', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento da chamada', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte da chamada', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
        { name: 'buttonLabel', label: 'Texto do botão' },
        { name: 'buttonUrl', label: 'Destino do botão' },
        { name: 'videoUrl', label: 'Vídeo de fundo', media: true, allowedTypes: ['video'] },
      ],
    },
    {
      name: 'locutora/internal-hero',
      title: 'Locutora — Cabeçalho interno',
      icon: 'cover-image',
      alwaysEdit: true,
      fields: [
        { name: 'title', label: 'Título da página', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
        { name: 'backgroundUrl', label: 'Imagem de fundo', media: true },
      ],
    },
    {
      name: 'locutora/about-story',
      title: 'Locutora — História',
      icon: 'book-alt',
      alwaysEdit: true,
      fields: [
        { name: 'title', label: 'Título', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
        { name: 'missionTitle', label: 'Título: Missão' },
        { name: 'missionText', label: 'Texto da missão', wysiwyg: true, rows: 8 },
        { name: 'visionTitle', label: 'Título: Visão' },
        { name: 'visionText', label: 'Texto da visão', wysiwyg: true, rows: 8 },
        { name: 'valuesTitle', label: 'Título: Valores' },
        { name: 'valuesText', label: 'Texto dos valores', wysiwyg: true, rows: 8 },
        { name: 'imageUrl', label: 'Imagem do estúdio', media: true },
        { name: 'imageAlt', label: 'Descrição da imagem' },
      ],
    },
    {
      name: 'locutora/about-bio',
      title: 'Locutora — Biografia',
      icon: 'admin-users',
      alwaysEdit: true,
      fields: [
        { name: 'title', label: 'Nome', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
        { name: 'paragraph1', label: 'Biografia — parágrafo 1', wysiwyg: true, rows: 8 },
        { name: 'paragraph2', label: 'Biografia — parágrafo 2', wysiwyg: true, rows: 8 },
        { name: 'paragraph3', label: 'Biografia — parágrafo 3', wysiwyg: true, rows: 8 },
        { name: 'paragraph4', label: 'Biografia — parágrafo 4', wysiwyg: true, rows: 8 },
        { name: 'paragraph5', label: 'Biografia — parágrafo 5', wysiwyg: true, rows: 8 },
        { name: 'paragraph6', label: 'Biografia — parágrafo 6', wysiwyg: true, rows: 8 },
        { name: 'imageUrl', label: 'Retrato', media: true },
        { name: 'imageAlt', label: 'Descrição do retrato' },
      ],
    },
    {
      name: 'locutora/brands',
      title: 'Locutora — Marcas',
      icon: 'grid-view',
      alwaysEdit: true,
      fields: [
        { name: 'title', label: 'Título', richtext: true },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
        { name: 'images', label: 'Logotipos', gallery: true },
      ],
    },
    {
      name: 'locutora/audio-showcase',
      title: 'Locutora — Áudios e vídeos',
      icon: 'playlist-audio',
      fields: [
        { name: 'title', label: 'Título da seção', richtext: true, help: 'Use Enter para controlar a quebra do título e a barra do campo para aplicar negrito ou itálico.' },
        { name: 'titleAlign', label: 'Alinhamento do título', options: [{ label: 'Padrão', value: '' }, { label: 'Esquerda', value: 'left' }, { label: 'Centralizado', value: 'center' }, { label: 'Direita', value: 'right' }] },
        { name: 'titleFont', label: 'Fonte do título', options: [{ label: 'Padrão do tema', value: '' }, { label: 'Montserrat', value: 'montserrat' }, { label: 'Arial', value: 'arial' }, { label: 'Georgia', value: 'georgia' }] },
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
      apiVersion: 2,
      title: definition.title,
      icon: definition.icon,
      category: 'design',
      supports: { html: false, reusable: false },
      edit: editor(definition.fields, definition.title, definition.alwaysEdit === true),
      save: function () { return null; },
    });
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
