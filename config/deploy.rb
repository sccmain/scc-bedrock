set :application, 'connectchristiancounseling'
set :repo_url, 'git@github.com:thuijssoon/c3.git'

# Branch options
# Prompts for the branch name (defaults to current branch)
#ask :branch, proc { `git rev-parse --abbrev-ref HEAD`.chomp }

# Sets branch to current one
#set :branch, proc { `git rev-parse --abbrev-ref HEAD`.chomp }

# Hardcodes branch to always be master
# This could be overridden in a stage config file
set :branch, :master

set :deploy_to, "/srv/www/#{fetch(:application)}/htdocs"

set :log_level, :debug

set :linked_files, %w{.env}
set :linked_dirs, %w{app/uploads}

# namespace :deploy do
#   before :starting, 'composer:install_executable'
# end

namespace :deploy do

  desc 'Restart application'
  task :restart do
    on roles(:app), in: :sequence, wait: 5 do
      # This task is required by Capistrano but can be a no-op
      # Your restart mechanism here, for example:
      # execute :service, :nginx, :reload
    end
  end

end
