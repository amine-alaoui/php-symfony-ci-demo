pipeline {
  agent any

  options {
    timestamps()
    timeout(time: 30, unit: 'MINUTES')
    disableConcurrentBuilds()
  }

  environment {
    PROJECT_VERSION = "1.0.${BUILD_NUMBER}"
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
            synchronous: false,
            dependencyTrackApiKey: DTRACK_API_KEY,
            dependencyTrackFrontendUrl: DTRACK_URL
          )
        }
      }
    }

    stage('Package') {
      steps {
        sh '''
          mkdir -p build
          tar --exclude=.git --exclude=vendor --exclude=var/cache -czf build/php-symfony-ci-demo-${PROJECT_VERSION}.tar.gz .
        '''
        archiveArtifacts artifacts: 'build/*.tar.gz', fingerprint: true
      }
    }

    stage('Publish Nexus') {
      when {
        branch 'main'
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
  }

  post {
    always {
      cleanWs()
    }
  }
}
