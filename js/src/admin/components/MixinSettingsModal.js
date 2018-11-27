import SettingsModal from 'flarum/components/SettingsModal';

export default class MixinSettingsModal extends SettingsModal {
  className() {
    return 'MixinSettingsModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-auth-mixin.admin.mixin_settings.title');
  }

  form() {
    return [
      <div className="Form-group">
        <label>{app.translator.trans('flarum-auth-mixin.admin.mixin_settings.client_id_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-auth-github.client_id')}/>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('flarum-auth-github.admin.github_settings.client_secret_label')}</label>
        <input className="FormControl" bidi={this.setting('flarum-auth-github.client_secret')}/>
      </div>
    ];
  }
}
