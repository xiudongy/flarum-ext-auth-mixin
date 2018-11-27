import app from 'flarum/app';

import MixinSettingsModal from './components/MixinSettingsModal';

app.initializers.add('flarum-auth-mixin', () => {
  app.extensionSettings['flarum-auth-mixin'] = () => app.modal.show(new MixinSettingsModal());
});
