set :stage, :development

# Simple Role Syntax
# ==================
#role :app, %w{deploy@example.com}
#role :web, %w{deploy@example.com}
#role :db,  %w{deploy@example.com}

# Extended Server Syntax
# ======================
server 'connectchristiancounseling.dev', user: 'vagrant', roles: %w{web app db}

set :deploy_to, "/srv/www/#{fetch(:application)}/htdocs"

# you can set custom ssh options
# it's possible to pass any option but you need to keep in mind that net/ssh understand limited list of options
# you can see them in [net/ssh documentation](http://net-ssh.github.io/net-ssh/classes/Net/SSH.html#method-c-start)
# set it globally
#  set :ssh_options, {
#    keys: %w(/home/rlisowski/.ssh/id_rsa),
#    forward_agent: false,
#    auth_methods: %w(password)
#  }

set :ssh_options, {
	keys: ['~/.vagrant.d/insecure_private_key'],
	forward_agent: true
}

fetch(:default_env).merge!(wp_env: :development)

