import { extend } from 'flarum/extend';
import app from 'flarum/app';
import LogInButtons from 'flarum/components/LogInButtons';
import LogInButton from 'flarum/components/LogInButton';

app.initializers.add('flarum-auth-mixin', () => {
  extend(LogInButtons.prototype, 'items', function(items) {
    items.add('mixin',
      <LogInButton
        className="Button LogInButton--mixin"
        icon="fab fa-mixin"
        path="/auth/mixin">
        {app.translator.trans('flarum-auth-mixin.forum.log_in.with_mixin_button')}
      </LogInButton>
    );
  });
});
