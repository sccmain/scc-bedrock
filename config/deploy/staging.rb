set :stage, :staging

set :deploy_to, "/var/www/vhosts/z2wp-sg7m.accessdomain.com/christiansextreatment.com"
set :tmp_dir, "/tmp2"

# SSHKit.config.command_map[:composer] = "#{shared_path.join("composer.phar")}"

SSHKit.config.command_map[:composer] = "~/composer.phar"

# Simple Role Syntax
# ==================
#role :app, %w{deploy@example.com}
#role :web, %w{deploy@example.com}
#role :db,  %w{deploy@example.com}

# Extended Server Syntax
# ======================
server '70.32.75.242', user: 'root', roles: %w{web app db}

# you can set custom ssh options
# it's possible to pass any option but you need to keep in mind that net/ssh understand limited list of options
# you can see them in [net/ssh documentation](http://net-ssh.github.io/net-ssh/classes/Net/SSH.html#method-c-start)
# set it globally
set :ssh_options, { :forward_agent => true }

fetch(:default_env).merge!(wp_env: :staging)

