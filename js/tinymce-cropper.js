(function () {
  const global = window;
  const baseConfig = global.tinymce7CropperConfig || {};
  const defaultLabels = {
    editTooltip: '画像を編集',
    modalTitle: '画像の編集',
    apply: '適用',
    cancel: 'キャンセル',
    rotateLeft: '左回転',
    rotateRight: '右回転',
    reset: 'リセット',
    zoomIn: '拡大',
    zoomOut: '縮小'
  };
  const defaultCropperOptions = {
    viewMode: 1,
    autoCropArea: 1,
    responsive: true,
    movable: true,
    zoomable: true,
    rotatable: true,
    scalable: true,
    checkOrientation: true
  };
  const defaultCanvasOptions = {
    maxWidth: null,
    maxHeight: null
  };
  const options = Object.assign(
    {
      enabled: false,
      cssUrl: 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css',
      jsUrl: 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js',
      enableDoubleClick: true,
      outputMimeType: 'image/png',
      outputQuality: 0.92,
      labels: {},
      cropperOptions: {},
      canvas: {}
    },
    baseConfig || {}
  );
  options.labels = Object.assign({}, defaultLabels, baseConfig.labels || {});
  options.cropperOptions = Object.assign({}, defaultCropperOptions, baseConfig.cropperOptions || {});
  options.canvas = Object.assign({}, defaultCanvasOptions, baseConfig.canvas || {});

  if (!options.enabled) {
    return;
  }

  const overlayStyleId = 'tinymce7-cropper-style';
  const scriptState = {
    assetsPromise: null,
    tinymceAttached: false
  };

  function ensureModalStyles() {
    if (document.getElementById(overlayStyleId)) {
      return;
    }

    const style = document.createElement('style');
    style.id = overlayStyleId;
    style.type = 'text/css';
    style.textContent = [
      '.tinymce7-cropper-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);display:flex;align-items:center;justify-content:center;z-index:100000;}',
      '.tinymce7-cropper-dialog{background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,0.3);max-width:90vw;max-height:90vh;width:720px;display:flex;flex-direction:column;gap:16px;padding:16px;box-sizing:border-box;}',
      '.tinymce7-cropper-title{font-size:16px;font-weight:600;margin:0;}',
      '.tinymce7-cropper-canvas{flex:1 1 auto;min-height:240px;display:flex;align-items:center;justify-content:center;overflow:hidden;}',
      '.tinymce7-cropper-canvas img{max-width:100%;max-height:100%;display:block;}',
      '.tinymce7-cropper-toolbar{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;}',
      '.tinymce7-cropper-toolbar button{padding:6px 12px;font-size:14px;border-radius:4px;border:1px solid #c3c3c3;background:#f3f3f3;cursor:pointer;transition:background 0.2s,border-color 0.2s;}',
      '.tinymce7-cropper-toolbar button:hover{background:#e8e8e8;border-color:#a7a7a7;}',
      '.tinymce7-cropper-actions{display:flex;justify-content:flex-end;gap:8px;}',
      '.tinymce7-cropper-actions button{padding:8px 16px;font-size:14px;border-radius:4px;border:1px solid transparent;cursor:pointer;}',
      '.tinymce7-cropper-apply{background:#007acc;color:#fff;}',
      '.tinymce7-cropper-apply:hover{background:#0061a8;}',
      '.tinymce7-cropper-cancel{background:#fff;color:#333;border-color:#c3c3c3;}',
      '.tinymce7-cropper-cancel:hover{background:#f6f6f6;}',
      '@media (max-width: 768px){.tinymce7-cropper-dialog{width:95vw;padding:12px;}.tinymce7-cropper-toolbar button,.tinymce7-cropper-actions button{flex:1 1 calc(50% - 8px);} }'
    ].join('');
    document.head.appendChild(style);
  }

  function loadCss(url) {
    if (!url) {
      return Promise.resolve();
    }

    const existing = document.querySelector('link[data-tinymce7-cropper="' + url + '"]');
    if (existing) {
      if (existing.dataset.loaded === 'true') {
        return Promise.resolve();
      }

      return new Promise(function (resolve, reject) {
        existing.addEventListener('load', resolve, { once: true });
        existing.addEventListener('error', function () {
          reject(new Error('Failed to load CSS: ' + url));
        }, { once: true });
      });
    }

    return new Promise(function (resolve, reject) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = url;
      link.dataset.tinymce7Cropper = url;
      link.onload = function () {
        link.dataset.loaded = 'true';
        resolve();
      };
      link.onerror = function () {
        reject(new Error('Failed to load CSS: ' + url));
      };
      document.head.appendChild(link);
    });
  }

  function loadScript(url) {
    if (!url) {
      return Promise.resolve();
    }

    const existing = document.querySelector('script[data-tinymce7-cropper="' + url + '"]');
    if (existing) {
      if (existing.dataset.loaded === 'true') {
        return Promise.resolve();
      }

      return new Promise(function (resolve, reject) {
        existing.addEventListener('load', resolve, { once: true });
        existing.addEventListener('error', function () {
          reject(new Error('Failed to load script: ' + url));
        }, { once: true });
      });
    }

    return new Promise(function (resolve, reject) {
      const script = document.createElement('script');
      script.src = url;
      script.async = true;
      script.dataset.tinymce7Cropper = url;
      script.onload = function () {
        script.dataset.loaded = 'true';
        resolve();
      };
      script.onerror = function () {
        reject(new Error('Failed to load script: ' + url));
      };
      document.head.appendChild(script);
    });
  }

  function ensureCropperAssets() {
    if (!scriptState.assetsPromise) {
      scriptState.assetsPromise = Promise.all([
        loadCss(options.cssUrl),
        loadScript(options.jsUrl)
      ]).then(function () {
        if (typeof global.Cropper === 'undefined') {
          throw new Error('Cropper.js is not available.');
        }

        return global.Cropper;
      });
    }

    return scriptState.assetsPromise;
  }

  function closeModal(overlay, cropper, cleanup) {
    if (cropper) {
      cropper.destroy();
    }

    if (overlay && overlay.parentNode) {
      overlay.parentNode.removeChild(overlay);
    }

    if (typeof cleanup === 'function') {
      cleanup();
    }
  }

  function openCropperModal(editor, imgNode) {
    if (!imgNode || imgNode.nodeName !== 'IMG') {
      return;
    }

    const src = imgNode.getAttribute('src') || '';
    if (!src) {
      return;
    }

    // 相対URLを絶対URLに変換
    let absoluteSrc = src;
    if (!/^https?:\/\//i.test(src) && !/^data:/i.test(src)) {
      // /で始まる絶対パスの場合は、ホスト部分のみを追加
      if (src.charAt(0) === '/') {
        const origin = window.location.origin;
        absoluteSrc = origin + src;
      } else {
        // 相対パスの場合、TinyMCEのdocument_base_urlまたはサイトルートを使用
        let baseUrl = window.location.origin + '/';
        if (editor && editor.settings && editor.settings.document_base_url) {
          baseUrl = editor.settings.document_base_url;
        }
        try {
          absoluteSrc = new URL(src, baseUrl).href;
        } catch (e) {
          console.error('TinyMCE7 Cropper: Failed to resolve image URL', e);
          absoluteSrc = src;
        }
      }
    }

    ensureCropperAssets().then(function (CropperClass) {
      ensureModalStyles();

      const overlay = document.createElement('div');
      overlay.className = 'tinymce7-cropper-overlay';
      overlay.setAttribute('role', 'presentation');

      const dialog = document.createElement('div');
      dialog.className = 'tinymce7-cropper-dialog';
      dialog.setAttribute('role', 'dialog');
      dialog.setAttribute('aria-modal', 'true');
      dialog.setAttribute('aria-label', options.labels.modalTitle);

      const title = document.createElement('h2');
      title.className = 'tinymce7-cropper-title';
      title.textContent = options.labels.modalTitle;
      dialog.appendChild(title);

      const canvasWrapper = document.createElement('div');
      canvasWrapper.className = 'tinymce7-cropper-canvas';

      const image = document.createElement('img');
      image.alt = options.labels.modalTitle;
      image.src = absoluteSrc;
      image.crossOrigin = 'anonymous'; // CORS対応
      canvasWrapper.appendChild(image);
      dialog.appendChild(canvasWrapper);

      const toolbar = document.createElement('div');
      toolbar.className = 'tinymce7-cropper-toolbar';
      dialog.appendChild(toolbar);

      const toolbarButtons = [
        { action: 'rotate-left', label: options.labels.rotateLeft },
        { action: 'rotate-right', label: options.labels.rotateRight },
        { action: 'zoom-in', label: options.labels.zoomIn },
        { action: 'zoom-out', label: options.labels.zoomOut },
        { action: 'reset', label: options.labels.reset }
      ];

      toolbarButtons.forEach(function (buttonConfig) {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.action = buttonConfig.action;
        button.textContent = buttonConfig.label;
        toolbar.appendChild(button);
      });

      const actions = document.createElement('div');
      actions.className = 'tinymce7-cropper-actions';

      const cancelButton = document.createElement('button');
      cancelButton.type = 'button';
      cancelButton.className = 'tinymce7-cropper-cancel';
      cancelButton.textContent = options.labels.cancel;

      const applyButton = document.createElement('button');
      applyButton.type = 'button';
      applyButton.className = 'tinymce7-cropper-apply';
      applyButton.textContent = options.labels.apply;

      actions.appendChild(cancelButton);
      actions.appendChild(applyButton);
      dialog.appendChild(actions);

      overlay.appendChild(dialog);
      document.body.appendChild(overlay);

      let cropperInstance = null;
      let isActive = true;
      const destroyListeners = [];

      // オーバーレイにフォーカスを設定してキーイベントを受け取れるようにする
      overlay.setAttribute('tabindex', '-1');
      overlay.focus();

      function registerListener(target, type, handler) {
        target.addEventListener(type, handler);
        destroyListeners.push(function () {
          target.removeEventListener(type, handler);
        });
      }

      function cleanup() {
        isActive = false;
        destroyListeners.forEach(function (off) {
          try {
            off();
          } catch (err) {
            /* noop */
          }
        });
        destroyListeners.length = 0;
      }

      function applyChanges() {
        if (!cropperInstance) {
          return;
        }

        let canvas;
        try {
          const canvasOptions = Object.assign({}, options.canvas);
          if (canvasOptions && typeof canvasOptions === 'object') {
            if (canvasOptions.maxWidth == null) {
              delete canvasOptions.maxWidth;
            }
            if (canvasOptions.maxHeight == null) {
              delete canvasOptions.maxHeight;
            }
          }
          canvas = cropperInstance.getCroppedCanvas(canvasOptions || {});
        } catch (error) {
          console.error('TinyMCE7 Cropper: Failed to create canvas.', error);
          if (editor && editor.windowManager) {
            editor.windowManager.alert('画像を更新できませんでした: ' + error.message);
          }
          return;
        }

        if (!canvas) {
          return;
        }

        let mimeType = typeof options.outputMimeType === 'string' && options.outputMimeType ? options.outputMimeType : 'image/png';
        let quality = typeof options.outputQuality === 'number' ? options.outputQuality : undefined;
        let dataUrl;

        try {
          if (quality !== undefined && mimeType !== 'image/png') {
            dataUrl = canvas.toDataURL(mimeType, quality);
          } else {
            dataUrl = canvas.toDataURL(mimeType);
          }
        } catch (error) {
          console.error('TinyMCE7 Cropper: Failed to export image (CORS制約の可能性).', error);
          if (editor && editor.windowManager) {
            editor.windowManager.alert('画像を更新できませんでした: ' + error.message + '\n\nCORS制約により、他のサーバ上の画像は編集できない可能性があります。');
          }
          return;
        }

        if (!dataUrl) {
          return;
        }

        const newWidth = canvas.width;
        const newHeight = canvas.height;

        editor.undoManager.transact(function () {
          // TinyMCE の DOM API を使って属性を設定
          editor.dom.setAttribs(imgNode, {
            'src': dataUrl,
            'width': String(newWidth),
            'height': String(newHeight)
          });
          // srcset 属性を削除
          editor.dom.setAttrib(imgNode, 'srcset', null);
        });

        editor.nodeChanged();

        // TinyMCE 7+ では fire() は非推奨、dispatch() を使用
        if (typeof editor.dispatch === 'function') {
          editor.dispatch('change');
        } else {
          editor.fire('change');
        }

        closeModal(overlay, cropperInstance, cleanup);
        editor.selection.select(imgNode);
      }

      registerListener(cancelButton, 'click', function () {
        closeModal(overlay, cropperInstance, cleanup);
      });

      registerListener(applyButton, 'click', function () {
        applyChanges();
      });

      registerListener(overlay, 'click', function (event) {
        if (event.target === overlay) {
          closeModal(overlay, cropperInstance, cleanup);
        }
      });

      // overlayでキーイベントをキャッチしてESCキーでモーダルを閉じる
      registerListener(overlay, 'keydown', function (event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
          event.preventDefault();
          event.stopPropagation();
          closeModal(overlay, cropperInstance, cleanup);
        }
      });

      // ドキュメントレベルでもESCキーをキャッチ（フォールバック）
      registerListener(document, 'keydown', function (event) {
        if (!isActive) {
          return;
        }

        if (event.key === 'Escape' || event.key === 'Esc') {
          event.preventDefault();
          event.stopPropagation();
          closeModal(overlay, cropperInstance, cleanup);
        }
      });

      toolbar.addEventListener('click', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
          return;
        }
        const action = target.dataset.action;
        if (!cropperInstance || !action) {
          return;
        }
        switch (action) {
          case 'rotate-left':
            cropperInstance.rotate(-90);
            break;
          case 'rotate-right':
            cropperInstance.rotate(90);
            break;
          case 'zoom-in':
            cropperInstance.zoom(0.1);
            break;
          case 'zoom-out':
            cropperInstance.zoom(-0.1);
            break;
          case 'reset':
            cropperInstance.reset();
            break;
          default:
            break;
        }
      });

      function initializeCropper() {
        try {
          const cropperConfig = Object.assign({}, options.cropperOptions);
          if (typeof cropperConfig.aspectRatio !== 'number') {
            cropperConfig.aspectRatio = NaN;
          }
          cropperInstance = new CropperClass(image, cropperConfig);
        } catch (error) {
          console.error('TinyMCE7 Cropper: Failed to initialise.', error);
          closeModal(overlay, cropperInstance, cleanup);
          if (editor && editor.windowManager) {
            editor.windowManager.alert('Cropper.js の初期化に失敗しました: ' + error.message);
          }
        }
      }

      if (image.complete && image.naturalWidth > 0) {
        initializeCropper();
      } else {
        registerListener(image, 'load', function () {
          if (!isActive) {
            return;
          }
          initializeCropper();
        });
        registerListener(image, 'error', function () {
          if (editor && editor.windowManager) {
            editor.windowManager.alert('画像を読み込めませんでした。');
          }
          closeModal(overlay, cropperInstance, cleanup);
        });
      }
    }).catch(function (error) {
      console.error('TinyMCE7 Cropper: Unable to open editor.', error);
      if (editor && editor.windowManager) {
        editor.windowManager.alert('Cropper.js を読み込めませんでした: ' + error.message);
      }
    });
  }

  function registerEditor(editor) {
    if (!editor || editor.tinymce7CropperInitialized) {
      return;
    }

    editor.tinymce7CropperInitialized = true;
    const buttonName = 'tinymce7CropperEditImage';

    editor.ui.registry.addButton(buttonName, {
      icon: 'edit-image',
      tooltip: options.labels.editTooltip,
      onAction: function () {
        const node = editor.selection.getNode();
        const image = node && node.nodeName === 'IMG' ? node : editor.dom.getParent(node, 'img');
        if (image) {
          openCropperModal(editor, image);
        }
      }
    });

    editor.ui.registry.addContextToolbar('tinymce7CropperToolbar', {
      predicate: function (node) {
        return node && node.nodeName === 'IMG';
      },
      items: buttonName,
      position: 'node',
      scope: 'node'
    });

    if (options.enableDoubleClick) {
      editor.on('DblClick', function (event) {
        if (event.target && event.target.nodeName === 'IMG') {
          event.preventDefault();
          openCropperModal(editor, event.target);
        }
      });
    }
  }

  function attachToTinymce() {
    if (scriptState.tinymceAttached) {
      return;
    }

    if (typeof global.tinymce === 'undefined' || !global.tinymce) {
      return;
    }

    scriptState.tinymceAttached = true;

    global.tinymce.on('AddEditor', function (event) {
      registerEditor(event.editor);
    });

    if (Array.isArray(global.tinymce.editors)) {
      global.tinymce.editors.forEach(function (editor) {
        registerEditor(editor);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachToTinymce);
  }

  attachToTinymce();
})();
