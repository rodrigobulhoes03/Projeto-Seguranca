create database if not exists gestao_bolsas;
use gestao_bolsas;

create table utilizadores(
    id int unsigned auto_increment primary key,
    username varchar(50)  not null unique,
    password_hash varchar(255) not null,
    perfil enum('candidato', 'tecnico') not null default 'candidato',
    nome varchar(100) not null,
    email varchar(100) not null unique,
    morada varchar(200) null,
    telefone varchar(20)  null,
    nif char(9) null,
    criado_em datetime not null default current_timestamp,
    constraint ck_nif check (nif is null or nif regexp '^[0-9]{9}$')
);

create table bolsas(
    id int unsigned auto_increment primary key,
    designacao varchar(150) not null,
    tipo_apoio varchar(80) not null,
    area_estudo varchar(80) not null,
    requisitos text not null,
    montante decimal(10,2) not null,
    prazo date not null,
    constraint ck_montante check (montante >= 0)
);

create table candidaturas(
  id int unsigned auto_increment primary key,
  id_candidato int unsigned not null,
  id_bolsa int unsigned not null,
  id_tecnico int unsigned null,
  instituicao varchar(150) not null,
  curso varchar(150) not null,
  rendimento_agregado decimal(10,2) not null,
  iban varchar(34) not null,
  estado enum('submetida','em_analise','aprovada','rejeitada', 'aguarda_documentacao','cancelada')  not null default 'submetida',
  data_submissao datetime not null default current_timestamp,
  unique (id_candidato, id_bolsa),
  foreign key (id_candidato) references utilizadores(id),
  foreign key (id_bolsa) references bolsas(id),
  foreign key (id_tecnico) references utilizadores(id),
  constraint ck_rendimento check (rendimento_agregado >= 0)
);

create table documentos(
  id int unsigned auto_increment primary key,
  id_candidatura int unsigned not null,
  nome_original varchar(255) not null,
  nome_ficheiro varchar(100) not null unique,
  tipo_mime varchar(100) not null,
  data_upload datetime not null default current_timestamp,
  foreign key (id_candidatura) references candidaturas(id) on delete cascade
);

create table esclarecimentos (
  id int unsigned auto_increment primary key,
  id_candidatura int unsigned not null,
  id_tecnico int unsigned not null,
  pergunta text not null,
  resposta text null,
  data_pedido datetime not null default current_timestamp,
  data_resposta datetime null,
  foreign key (id_candidatura) references candidaturas(id) on delete cascade,
  foreign key (id_tecnico) references utilizadores(id)
);

create table observacoes(
  id int unsigned auto_increment primary key,
  id_candidatura int unsigned not null,
  id_tecnico int unsigned not null,
  texto text not null,
  data_registo datetime not null default current_timestamp,
  foreign key (id_candidatura) references candidaturas(id) on delete cascade,
  foreign key (id_tecnico) references utilizadores(id)
);

create table historico_estados(
  id int unsigned auto_increment primary key,
  id_candidatura int unsigned not null,
  estado_anterior varchar(30) null,
  estado_novo varchar(30) not null,
  id_utilizador int unsigned not null,
  data_alteracao datetime not null default current_timestamp,
  foreign key (id_candidatura) references candidaturas(id) on delete cascade,
  foreign key (id_utilizador)  references utilizadores(id)
);

insert into bolsas (designacao, tipo_apoio, area_estudo, requisitos, montante, prazo) values
('Bolsa de Mérito Científico', 'Bolsa de estudo', 'Ciências Exatas',
 'Média igual ou superior a 16 valores e inscrição em curso da área das ciências exatas.',
 1800.00, '2027-01-31'),
('Apoio à Mobilidade Erasmus', 'Apoio à mobilidade', 'Todas',
 'Aceitação numa instituição de ensino superior estrangeira ao abrigo do programa Erasmus.',
 2400.00, '2027-02-15'),
('Bolsa para Estudantes Deslocados', 'Apoio financeiro', 'Todas',
 'Residência habitual a mais de 50 km da instituição de ensino e rendimento do agregado abaixo do limite definido.',
 1100.00, '2026-12-20'),
('Prémio de Excelência em Engenharia', 'Prémio', 'Engenharia',
 'Melhor classificação do curso no ano anterior e sem unidades curriculares em atraso.',
 2000.00, '2027-01-15'),
('Apoio à Aquisição de Material Informático', 'Apoio financeiro', 'Informática',
 'Inscrição em curso da área da informática e declaração de necessidade de equipamento.',
 600.00, '2026-12-10'),
('Bolsa de Investigação Júnior', 'Bolsa de investigação', 'Ciências da Saúde',
 'Média igual ou superior a 15 valores e carta de motivação com proposta de projeto.',
 3000.00, '2027-03-01'),
('Apoio Social a Famílias Numerosas', 'Apoio social', 'Todas',
 'Agregado familiar com três ou mais filhos a cargo e comprovativo de rendimentos.',
 950.00, '2026-12-31'),
('Bolsa de Estudo para Artes e Design', 'Bolsa de estudo', 'Artes e Design',
 'Inscrição em curso de artes ou design e apresentação de um portefólio.',
 1300.00, '2027-01-20'),
('Bolsa de Formação em Línguas Estrangeiras', 'Bolsa de formação', 'Línguas e Humanidades',
 'Inscrição num curso de línguas reconhecido e nível mínimo B1 comprovado.',
 700.00, '2026-11-30'),
('Bolsa de Mérito 2025/2026', 'Bolsa de estudo', 'Todas',
 'Média igual ou superior a 14 valores no ano letivo 2024/2025.',
 1400.00, '2026-09-30');