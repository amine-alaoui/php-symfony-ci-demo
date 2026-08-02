pipeline {
  agent any

  options {
    timestamps()
    timeout(time: 30, unit: 'MINUTES')
    disableConcurrentBuilds()
  }

  environment {
    PROJECT_VERSION = "1.0.${BUILD_NUMBER}"
    ANSIBLE_REPO_URL = "https://github.com/amine-alaoui/ansible-devsecops-platform.git"
    CONFIG_REPO_URL = "git@github.com:amine-alaoui/php-symfony-ci-demo-config.git"
    PHP_APP_HOST = "10.249.0.35"
    PHP_APP_SSH_USER = "root"
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    stage('Install') {
      steps {
        sh 'composer install --no-interaction --prefer-dist --no-progress'
      }
    }

    stage('Test') {
      steps {
        sh 'mkdir -p var'
        sh 'vendor/bin/phpunit --log-junit var/junit.xml --coverage-clover var/coverage.xml'
      }
      post {
        always {
          junit allowEmptyResults: true, testResults: 'var/junit.xml'
        }
      }
    }

    stage('SonarQube') {
      steps {
        withCredentials([string(credentialsId: 'sonar-token', variable: 'SONAR_TOKEN')]) {
          sh '''
            sonar-scanner \
              -Dsonar.host.url=${SONAR_HOST_URL} \
              -Dsonar.token=${SONAR_TOKEN}
          '''
        }
      }
    }

    stage('Dependency-Check') {
      steps {
        withCredentials([string(credentialsId: 'nvd-api-key', variable: 'NVD_API_KEY')]) {
          sh '''
            dependency-check \
              --project php-symfony-ci-demo \
              --scan . \
              --data /var/lib/jenkins/dependency-check-data \
              --nvdApiKey "${NVD_API_KEY}" \
              --nvdApiDelay 4000 \
              --disableAssembly \
              --format HTML \
              --format XML \
              --out .
          '''
        }
        publishHTML([
          allowMissing: true,
          alwaysLinkToLastBuild: true,
          keepAll: true,
          reportDir: '.',
          reportFiles: 'dependency-check-report.html',
          reportName: 'OWASP Dependency-Check'
        ])
      }
    }

    stage('Generate SBOM') {
      steps {
        sh '''
          if command -v cyclonedx-php-composer >/dev/null 2>&1; then
            cyclonedx-php-composer make-sbom --output-file=bom.xml
          else
            composer show --format=json > var/composer-dependencies.json
            echo '<bom xmlns="http://cyclonedx.org/schema/bom/1.5" version="1"></bom>' > bom.xml
          fi
        '''
        archiveArtifacts allowEmptyArchive: true, artifacts: 'bom.xml,var/composer-dependencies.json'
      }
    }

    stage('Dependency-Track') {
      steps {
        withCredentials([string(credentialsId: 'dependency-track-api-key', variable: 'DTRACK_API_KEY')]) {
          dependencyTrackPublisher(
            artifact: 'bom.xml',
            projectName: 'php-symfony-ci-demo',
            projectVersion: "${PROJECT_VERSION}",
            autoCreateProjects: true,
            synchronous: false,
            dependencyTrackApiKey: DTRACK_API_KEY,
            dependencyTrackUrl: DTRACK_URL,
            dependencyTrackFrontendUrl: DTRACK_URL
          )
        }
      }
    }

    stage('Package') {
      steps {
        sh '''
          mkdir -p build
          tar -czf build/php-symfony-ci-demo-${PROJECT_VERSION}.tar.gz \
            composer.json \
            composer.lock \
            config \
            public \
            src
        '''
        archiveArtifacts artifacts: 'build/*.tar.gz', fingerprint: true
      }
    }

    stage('Publish Nexus') {
      when {
        anyOf {
          branch 'dev'
          branch 'rct'
          branch 'main'
          branch 'master'
        }
      }
      steps {
        withCredentials([usernamePassword(credentialsId: 'nexus-credentials', usernameVariable: 'NEXUS_USER', passwordVariable: 'NEXUS_PASSWORD')]) {
          sh '''
            curl -f -u "${NEXUS_USER}:${NEXUS_PASSWORD}" \
              --upload-file build/php-symfony-ci-demo-${PROJECT_VERSION}.tar.gz \
              "${NEXUS_URL}/repository/${NEXUS_REPOSITORY}/php-symfony-ci-demo-${PROJECT_VERSION}.tar.gz"
          '''
        }
      }
    }

    stage('Deploy') {
      when {
        anyOf {
          branch 'dev'
          branch 'rct'
        }
      }
      steps {
        script {
          def deployEnv = env.BRANCH_NAME == 'rct' ? 'rct' : 'dev'

          dir('ansible-devsecops-platform') {
            git branch: 'main',
              credentialsId: 'git-credentials',
              url: "${ANSIBLE_REPO_URL}"

            sh 'mkdir -p .jenkins'
            writeFile file: '.jenkins/apps-inventory.yml', text: """
              ---
              all:
                children:
                  apps_server:
                    hosts:
                      php_app:
                        ansible_host: "${PHP_APP_HOST}"
                        ansible_user: "${PHP_APP_SSH_USER}"
                        ansible_python_interpreter: /usr/local/bin/ansible-python
            """.stripIndent()

            withCredentials([usernamePassword(credentialsId: 'nexus-credentials', usernameVariable: 'NEXUS_USER', passwordVariable: 'NEXUS_PASSWORD')]) {
              writeFile file: '.jenkins/deploy-vars.yml', text: """
                ---
                php_symfony_app_version: "${PROJECT_VERSION}"
                php_symfony_app_config_env: "${deployEnv}"
                php_symfony_app_config_repo: "${CONFIG_REPO_URL}"
                php_symfony_app_nexus_url: "${NEXUS_URL}"
                php_symfony_app_nexus_repository: "${NEXUS_REPOSITORY}"
                php_symfony_app_nexus_username: "${NEXUS_USER}"
                php_symfony_app_nexus_password: "${NEXUS_PASSWORD}"
              """.stripIndent()

              sshagent(credentials: ['ansible-ssh-key']) {
                sh '''
                  chmod 600 .jenkins/deploy-vars.yml
                  trap 'rm -f .jenkins/deploy-vars.yml' EXIT

                  ansible-playbook \
                    -i .jenkins/apps-inventory.yml \
                    playbooks/deploy/php-symfony-ci-demo.yml \
                    -e @.jenkins/deploy-vars.yml
                '''
              }
            }
          }
        }
      }
    }
  }

  post {
    always {
      cleanWs()
    }
  }
}
